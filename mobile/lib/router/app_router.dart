import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/admin/admin_screens.dart';
import '../features/auth/auth_screens.dart';
import '../features/dashboard/dashboard_screen.dart';
import '../features/profile/profile_screen.dart';
import '../features/projects/project_screens.dart';
import '../features/tasks/task_screens.dart';
import '../features/team/team_screens.dart';
import '../providers/providers.dart';
import '../widgets/widgets.dart';

final _rootKey = GlobalKey<NavigatorState>();
final _shellKey = GlobalKey<NavigatorState>();

final routerProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.listen(authProvider, (_, _) => refresh.value++);
  ref.onDispose(refresh.dispose);

  return GoRouter(
    navigatorKey: _rootKey,
    initialLocation: '/dashboard',
    refreshListenable: refresh,
    redirect: (context, state) {
      final auth = ref.read(authProvider);
      if (auth.loading) {
        return null;
      }

      final loggingIn = state.matchedLocation == '/login' ||
          state.matchedLocation == '/forgot-password' ||
          state.matchedLocation == '/reset-password';

      if (!auth.isAuthenticated) {
        return loggingIn ? null : '/login';
      }

      if (loggingIn) {
        return auth.user!.isAdmin ? '/admin' : '/dashboard';
      }

      if (auth.user!.isAdmin && (state.matchedLocation == '/dashboard' || state.matchedLocation == '/tasks')) {
        return '/admin';
      }

      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(path: '/forgot-password', builder: (context, state) => const ForgotPasswordScreen()),
      GoRoute(path: '/reset-password', builder: (context, state) => const ResetPasswordScreen()),
      ShellRoute(
        navigatorKey: _shellKey,
        builder: (context, state, child) => AppShell(child: child),
        routes: [
          GoRoute(path: '/dashboard', builder: (context, state) => const DashboardScreen()),
          GoRoute(path: '/tasks', builder: (context, state) => const TaskListScreen()),
          GoRoute(path: '/tasks/history', builder: (context, state) => const TaskHistoryScreen()),
          GoRoute(
            path: '/tasks/:id',
            builder: (context, state) => TaskDetailScreen(taskId: int.parse(state.pathParameters['id']!)),
          ),
          GoRoute(
            path: '/tasks/:id/edit',
            builder: (context, state) => TaskFormScreen(taskId: int.parse(state.pathParameters['id']!)),
          ),
          GoRoute(path: '/projects', builder: (context, state) => const ProjectListScreen()),
          GoRoute(
            path: '/projects/:id',
            builder: (context, state) => ProjectDetailScreen(projectId: int.parse(state.pathParameters['id']!)),
          ),
          GoRoute(
            path: '/projects/:id/tasks/create',
            builder: (context, state) => TaskFormScreen(projectId: int.parse(state.pathParameters['id']!)),
          ),
          GoRoute(path: '/team/tasks', builder: (context, state) => const TeamTasksScreen()),
          GoRoute(
            path: '/team/members/:id',
            builder: (context, state) => TeamMemberScreen(userId: int.parse(state.pathParameters['id']!)),
          ),
          GoRoute(path: '/profile', builder: (context, state) => const ProfileScreen()),
          GoRoute(path: '/admin', builder: (context, state) => const AdminDashboardScreen()),
          GoRoute(path: '/admin/users', builder: (context, state) => const AdminUsersScreen()),
          GoRoute(path: '/admin/users/create', builder: (context, state) => const AdminUserFormScreen()),
          GoRoute(
            path: '/admin/users/:id',
            builder: (context, state) => AdminUserFormScreen(userId: int.parse(state.pathParameters['id']!)),
          ),
          GoRoute(path: '/admin/teams', builder: (context, state) => const AdminTeamsScreen()),
          GoRoute(path: '/admin/teams/create', builder: (context, state) => const AdminTeamFormScreen()),
          GoRoute(
            path: '/admin/teams/:id/edit',
            builder: (context, state) => AdminTeamFormScreen(teamId: int.parse(state.pathParameters['id']!)),
          ),
          GoRoute(path: '/admin/projects', builder: (context, state) => const AdminProjectsScreen()),
          GoRoute(path: '/admin/projects/create', builder: (context, state) => const AdminProjectFormScreen()),
          GoRoute(
            path: '/admin/projects/:id/edit',
            builder: (context, state) => AdminProjectFormScreen(projectId: int.parse(state.pathParameters['id']!)),
          ),
        ],
      ),
    ],
  );
});
