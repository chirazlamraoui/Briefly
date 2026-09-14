import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../l10n/app_localizations.dart';
import '../../models/models.dart';
import '../../providers/providers.dart';
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
                padding: const EdgeInsets.all(16),
                children: [
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          CompletionRing(rate: data.overallRate),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(l10n.myCompletionRate, style: Theme.of(context).textTheme.titleMedium),
                                Text('${data.doneCount}/${data.totalCount}'),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(l10n.myTasks, style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  if (data.tasks.isEmpty) EmptyState(message: l10n.emptyTasks),
                  for (final row in data.tasks.take(5))
                    ListTile(
                      title: Text(row.task.title),
                      subtitle: Text(row.task.project?.name ?? ''),
                      trailing: StatusPill(status: row.task.status),
                      onTap: () => context.push('/tasks/${row.task.id}'),
                    ),
                  for (final section in data.ledTeams) ...[
                    const SizedBox(height: 24),
                    Text(section.team.name, style: Theme.of(context).textTheme.titleLarge),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      children: [
                        _statChip(l10n.inProgress, section.stats['in_progress'] ?? 0),
                        _statChip(l10n.blocked, section.stats['blocked'] ?? 0),
                        _statChip(l10n.statusDone, section.stats['done'] ?? 0),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(l10n.memberProgress, style: Theme.of(context).textTheme.titleMedium),
                    for (final row in section.memberRates.take(5))
                      ListTile(
                        title: Text(row.name),
                        trailing: Text('${row.rate}%'),
                        onTap: row.id == null ? null : () => context.push('/team/members/${row.id}'),
                      ),
                    Text(l10n.projectProgress, style: Theme.of(context).textTheme.titleMedium),
                    for (final row in section.projectRates.take(5))
                      ListTile(
                        title: Text(row.name),
                        trailing: Text('${row.rate}%'),
                        onTap: row.id == null ? null : () => context.push('/projects/${row.id}'),
                      ),
                    if (section.blockedTasks.isNotEmpty) ...[
                      Text(l10n.blockedTasks, style: Theme.of(context).textTheme.titleMedium),
                      for (final task in section.blockedTasks)
                        ListTile(
                          title: Text(task.title),
                          trailing: const StatusPill(status: 'BLOCKED'),
                          onTap: () => context.push('/tasks/${task.id}'),
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

  Widget _statChip(String label, int value) {
    return Chip(label: Text('$label · $value'));
  }
}
