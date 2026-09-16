import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../l10n/app_localizations.dart';
import '../../models/models.dart';
import '../../providers/providers.dart';
import '../../theme/briefly_theme.dart';
import '../../widgets/widgets.dart';

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  late Future<DashboardData> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).dashboard();
  }

  void _reload() {
    setState(() => _future = ref.read(apiClientProvider).dashboard());
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.dashboard)),
      body: FutureBuilder<DashboardData>(
        future: _future,
        builder: (context, snapshot) {
          return AsyncBody<DashboardData>(
            snapshot: snapshot,
            onRetry: _reload,
            builder: (data) {
              return ListView(
                padding: BrieflySpacing.page,
                children: [
                  BrieflyCard(
                    child: Row(
                      children: [
                        CompletionRing(rate: data.overallRate),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                l10n.myCompletionRate,
                                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '${data.doneCount}/${data.totalCount}',
                                style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  SectionHeader(title: l10n.myTasks),
                  if (data.tasks.isEmpty)
                    BrieflyCard(
                      child: EmptyState(message: l10n.emptyTasks, icon: Icons.checklist_outlined, compact: true),
                    )
                  else
                    GroupedCard(
                      children: [
                        for (final row in data.tasks.take(5))
                          TaskListTile(
                            title: row.task.title,
                            subtitle: row.task.project?.name,
                            status: row.task.status,
                            onTap: () => context.push('/tasks/${row.task.id}'),
                            grouped: true,
                          ),
                      ],
                    ),
                  for (final section in data.ledTeams) ...[
                    const SizedBox(height: 16),
                    SectionHeader(title: section.team.name),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        StatChip(label: l10n.inProgress, value: section.stats['in_progress'] ?? 0),
                        StatChip(label: l10n.blocked, value: section.stats['blocked'] ?? 0),
                        StatChip(label: l10n.statusDone, value: section.stats['done'] ?? 0),
                      ],
                    ),
                    const SizedBox(height: 8),
                    SectionHeader(title: l10n.memberProgress),
                    GroupedCard(
                      children: [
                        for (final row in section.memberRates.take(5))
                          RateListTile(
                            name: row.name,
                            rate: row.rate,
                            grouped: true,
                            onTap: row.id == null ? null : () => context.push('/team/members/${row.id}'),
                          ),
                      ],
                    ),
                    SectionHeader(title: l10n.projectProgress),
                    GroupedCard(
                      children: [
                        for (final row in section.projectRates.take(5))
                          RateListTile(
                            name: row.name,
                            rate: row.rate,
                            grouped: true,
                            onTap: row.id == null ? null : () => context.push('/projects/${row.id}'),
                          ),
                      ],
                    ),
                    if (section.blockedTasks.isNotEmpty) ...[
                      SectionHeader(title: l10n.blockedTasks),
                      GroupedCard(
                        children: [
                          for (final task in section.blockedTasks)
                            TaskListTile(
                              title: task.title,
                              status: 'BLOCKED',
                              grouped: true,
                              onTap: () => context.push('/tasks/${task.id}'),
                            ),
                        ],
                      ),
                    ],
                  ],
                ],
              );
            },
          );
        },
      ),
    );
  }
}
