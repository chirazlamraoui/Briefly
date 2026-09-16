import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../l10n/app_localizations.dart';
import '../../models/models.dart';
import '../../providers/providers.dart';
import '../../theme/briefly_theme.dart';
import '../../widgets/widgets.dart';

class TeamTasksScreen extends ConsumerStatefulWidget {
  const TeamTasksScreen({super.key});

  @override
  ConsumerState<TeamTasksScreen> createState() => _TeamTasksScreenState();
}

class _TeamTasksScreenState extends ConsumerState<TeamTasksScreen> {
  String? _status;
  late Future<({Map<String, int> stats, List<({AppTask task, AppTeam team})> tasks})> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).teamTasks();
  }

  void _reload([String? status]) {
    setState(() {
      _status = status;
      _future = ref.read(apiClientProvider).teamTasks(status: status);
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.teamTasks)),
      body: FutureBuilder(
        future: _future,
        builder: (context, snapshot) {
          return AsyncBody(
            snapshot: snapshot,
            onRetry: _reload,
            builder: (data) {
              return ListView(
                padding: BrieflySpacing.page,
                children: [
                  BrieflyCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            ChoiceChip(label: Text(l10n.all), selected: _status == null, onSelected: (_) => _reload()),
                            for (final status in taskStatuses)
                              ChoiceChip(
                                label: Text(statusLabel(l10n, status)),
                                selected: _status == status,
                                onSelected: (_) => _reload(status),
                              ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            StatChip(label: l10n.inProgress, value: data.stats['in_progress'] ?? 0),
                            StatChip(label: l10n.blocked, value: data.stats['blocked'] ?? 0),
                            StatChip(label: l10n.statusDone, value: data.stats['done'] ?? 0),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  if (data.tasks.isEmpty)
                    BrieflyCard(
                      child: EmptyState(message: l10n.emptyTasks, icon: Icons.groups_outlined, compact: true),
                    )
                  else
                    for (final row in data.tasks)
                      TaskListTile(
                        title: row.task.title,
                        subtitle: [row.team.name, row.task.assignee?.name].whereType<String>().join(' · '),
                        status: row.task.status,
                        onTap: () => context.push('/tasks/${row.task.id}'),
                      ),
                ],
              );
            },
          );
        },
      ),
    );
  }
}

class TeamMemberScreen extends ConsumerStatefulWidget {
  const TeamMemberScreen({super.key, required this.userId});

  final int userId;

  @override
  ConsumerState<TeamMemberScreen> createState() => _TeamMemberScreenState();
}

class _TeamMemberScreenState extends ConsumerState<TeamMemberScreen> {
  late Future<({AppUser user, List<AppTask> tasks, PaginatedUpdates updates})> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).teamMember(widget.userId);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final scheme = Theme.of(context).colorScheme;

    return FutureBuilder(
      future: _future,
      builder: (context, snapshot) {
        return AsyncBody(
          snapshot: snapshot,
          onRetry: () => setState(() => _future = ref.read(apiClientProvider).teamMember(widget.userId)),
          builder: (data) {
            return Scaffold(
              appBar: AppBar(title: Text(data.user.name)),
              body: ListView(
                padding: BrieflySpacing.page,
                children: [
                  BrieflyCard(
                    child: Row(
                      children: [
                        InitialAvatar(name: data.user.name, radius: 28),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                data.user.name,
                                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                              ),
                              if (data.user.jobTitle != null)
                                Text(
                                  data.user.jobTitle!,
                                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  SectionHeader(title: l10n.tasks),
                  if (data.tasks.isEmpty)
                    BrieflyCard(
                      child: EmptyState(message: l10n.emptyTasks, icon: Icons.checklist_outlined, compact: true),
                    )
                  else
                    GroupedCard(
                      children: [
                        for (final task in data.tasks)
                          TaskListTile(
                            title: task.title,
                            status: task.status,
                            grouped: true,
                            onTap: () => context.push('/tasks/${task.id}'),
                          ),
                      ],
                    ),
                  const SizedBox(height: 8),
                  SectionHeader(title: l10n.history),
                  if (data.updates.items.isEmpty)
                    BrieflyCard(
                      child: EmptyState(message: l10n.emptyHistory, icon: Icons.history, compact: true),
                    )
                  else
                    GroupedCard(
                      children: [
                        for (final update in data.updates.items)
                          TaskListTile(
                            title: update.task?.title ?? '',
                            subtitle: update.progressDone,
                            status: update.status,
                            grouped: true,
                          ),
                      ],
                    ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}
