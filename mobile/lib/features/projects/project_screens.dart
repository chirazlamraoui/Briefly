import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../l10n/app_localizations.dart';
import '../../models/models.dart';
import '../../providers/providers.dart';
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
                return EmptyState(message: l10n.emptyProjects);
              }

              return ListView.builder(
                itemCount: projects.length,
                itemBuilder: (context, index) {
                  final project = projects[index];
                  return ListTile(
                    title: Text(project.name),
                    subtitle: Text(project.teams.map((team) => team.name).join(', ')),
                    trailing: Text('${project.tasksCount ?? 0}'),
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
                padding: const EdgeInsets.all(16),
                children: [
                  if (data.project.description != null) Text(data.project.description!),
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: () => context.push('/projects/${widget.projectId}/tasks/create'),
                    child: Text(l10n.newTask),
                  ),
                  const SizedBox(height: 16),
                  if (data.tasks.isEmpty) EmptyState(message: l10n.emptyTasks),
                  for (final task in data.tasks)
                    ListTile(
                      title: Text(task.title),
                      subtitle: Text(task.assignee?.name ?? ''),
                      trailing: StatusPill(status: task.status),
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
