import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../l10n/app_localizations.dart';
import '../../models/models.dart';
import '../../providers/providers.dart';
import '../../theme/briefly_theme.dart';
import '../../widgets/widgets.dart';

/// Home after login. Loads GET /dashboard.
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
                  CompletionSummaryCard(
                    rate: data.overallRate,
                    label: l10n.myCompletionRate,
                    subtitle: '${data.doneCount}/${data.totalCount}',
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
                            title: row.title,
                            subtitle: row.project?.name,
                            status: row.status,
                            onTap: () => context.push('/tasks/${row.id}'),
                            grouped: true,
                          ),
                      ],
                    ),
                  for (final section in data.ledTeams) ...[
                    const SizedBox(height: 16),
                    SectionHeader(title: section.team.name),
                    TaskStatsChips(stats: section.stats),
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
