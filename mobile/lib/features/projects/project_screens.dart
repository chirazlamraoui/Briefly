import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../l10n/app_localizations.dart';
import '../../models/models.dart';
import '../../providers/providers.dart';
import '../../theme/briefly_theme.dart';
import '../../widgets/widgets.dart';

class ProjectListScreen extends ConsumerStatefulWidget {
  const ProjectListScreen({super.key});

  @override
  ConsumerState<ProjectListScreen> createState() => _ProjectListScreenState();
}

class _ProjectListScreenState extends ConsumerState<ProjectListScreen> {
  late Future<List<AppProject>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).projects();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.projects)),
      body: FutureBuilder<List<AppProject>>(
        future: _future,
        builder: (context, snapshot) {
          return AsyncBody<List<AppProject>>(
            snapshot: snapshot,
            onRetry: () => setState(() => _future = ref.read(apiClientProvider).projects()),
            builder: (projects) {
              if (projects.isEmpty) {
                return EmptyState(message: l10n.emptyProjects, icon: Icons.folder_outlined);
              }

              return ListView.builder(
                padding: BrieflySpacing.page,
                itemCount: projects.length,
                itemBuilder: (context, index) {
                  final project = projects[index];
                  return EntityListTile(
                    title: project.name,
                    subtitle: project.teams.map((team) => team.name).join(', '),
                    leading: const Icon(Icons.folder_outlined),
                    trailing: Text(
                      '${project.tasksCount ?? 0}',
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                    ),
                    onTap: () => context.push('/projects/${project.id}'),
                  );
                },
              );
            },
          );
        },
      ),
    );
  }
}

class ProjectDetailScreen extends ConsumerStatefulWidget {
  const ProjectDetailScreen({super.key, required this.projectId});

  final int projectId;

  @override
  ConsumerState<ProjectDetailScreen> createState() => _ProjectDetailScreenState();
}

class _ProjectDetailScreenState extends ConsumerState<ProjectDetailScreen> {
  late Future<({AppProject project, List<AppTask> tasks, List<AppUser> members})> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).project(widget.projectId);
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
          onRetry: () => setState(() => _future = ref.read(apiClientProvider).project(widget.projectId)),
          builder: (data) {
            return Scaffold(
              appBar: AppBar(
                title: Text(data.project.name),
                actions: [
                  IconButton(
                    onPressed: () => context.push('/projects/${widget.projectId}/tasks/create'),
                    icon: const Icon(Icons.add),
                  ),
                ],
              ),
              body: ListView(
                padding: BrieflySpacing.page,
                children: [
                  BrieflyCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          data.project.name,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                        ),
                        if (data.project.description != null && data.project.description!.isNotEmpty) ...[
                          const SizedBox(height: 8),
                          Text(
                            data.project.description!,
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
                          ),
                        ],
                        const SizedBox(height: 16),
                        FilledButton(
                          onPressed: () => context.push('/projects/${widget.projectId}/tasks/create'),
                          child: Text(l10n.newTask),
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
                    for (final task in data.tasks)
                      TaskListTile(
                        title: task.title,
                        subtitle: task.assignee?.name,
                        status: task.status,
                        onTap: () => context.push('/tasks/${task.id}'),
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
