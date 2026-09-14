import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../l10n/app_localizations.dart';
import '../../models/models.dart';
import '../../providers/providers.dart';
import '../../widgets/widgets.dart';
import '../tasks/task_screens.dart';

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
                padding: const EdgeInsets.all(16),
                children: [
                  Wrap(
                    spacing: 8,
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
                    children: [
                      Chip(label: Text('${l10n.inProgress} ${data.stats['in_progress'] ?? 0}')),
                      Chip(label: Text('${l10n.blocked} ${data.stats['blocked'] ?? 0}')),
                      Chip(label: Text('${l10n.statusDone} ${data.stats['done'] ?? 0}')),
                    ],
                  ),
                  const SizedBox(height: 16),
                  if (data.tasks.isEmpty) EmptyState(message: l10n.emptyTasks),
                  for (final row in data.tasks)
                    ListTile(
                      title: Text(row.task.title),
                      subtitle: Text('${row.team.name} · ${row.task.assignee?.name ?? ''}'),
                      trailing: StatusPill(status: row.task.status),
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
                padding: const EdgeInsets.all(16),
                children: [
                  if (data.user.jobTitle != null) Text(data.user.jobTitle!),
                  const SizedBox(height: 16),
                  Text(l10n.tasks, style: Theme.of(context).textTheme.titleMedium),
                  for (final task in data.tasks)
                    ListTile(
                      title: Text(task.title),
                      trailing: StatusPill(status: task.status),
                      onTap: () => context.push('/tasks/${task.id}'),
                    ),
                  const SizedBox(height: 16),
                  Text(l10n.history, style: Theme.of(context).textTheme.titleMedium),
                  for (final update in data.updates.items)
                    ListTile(
                      title: Text(update.task?.title ?? ''),
                      subtitle: Text(update.progressDone ?? ''),
                      trailing: StatusPill(status: update.status),
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
