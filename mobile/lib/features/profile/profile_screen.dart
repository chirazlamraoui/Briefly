import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_exception.dart';
import '../../l10n/app_localizations.dart';
import '../../providers/providers.dart';
import '../../theme/briefly_theme.dart';
import '../../widgets/widgets.dart';

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  late TextEditingController _name;
  late TextEditingController _email;
  final _current = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  String? _error;
  bool _savingProfile = false;
  bool _savingPassword = false;

  @override
  void initState() {
    super.initState();
    final user = ref.read(authProvider).user;
    _name = TextEditingController(text: user?.name ?? '');
    _email = TextEditingController(text: user?.email ?? '');
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _current.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _saveProfile() async {
    setState(() {
      _savingProfile = true;
      _error = null;
    });

    try {
      await ref.read(apiClientProvider).updateProfile(name: _name.text.trim(), email: _email.text.trim());
      await ref.read(authProvider.notifier).refreshUser();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(AppLocalizations.of(context)!.profileSaved)));
      }
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } finally {
      if (mounted) {
        setState(() => _savingProfile = false);
      }
    }
  }

  Future<void> _changePassword() async {
    final l10n = AppLocalizations.of(context)!;
    if (_password.text.length < 8) {
      setState(() => _error = l10n.passwordMinLength);
      return;
    }
    if (_password.text != _confirm.text) {
      setState(() => _error = l10n.passwordsDoNotMatch);
      return;
    }

    setState(() {
      _savingPassword = true;
      _error = null;
    });

    try {
      await ref.read(apiClientProvider).updatePassword(
            currentPassword: _current.text,
            password: _password.text,
            passwordConfirmation: _confirm.text,
          );
      _current.clear();
      _password.clear();
      _confirm.clear();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(l10n.passwordChanged)));
      }
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } finally {
      if (mounted) {
        setState(() => _savingPassword = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final locale = ref.watch(localeProvider);
    final themeMode = ref.watch(themeModeProvider);
    final user = ref.watch(authProvider).user;
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.profile)),
      body: ListView(
        padding: BrieflySpacing.page,
        children: [
          BrieflyCard(
            child: Row(
              children: [
                InitialAvatar(name: user?.name ?? '', radius: 28),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        user?.name ?? '',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                      ),
                      if (user?.jobTitle != null)
                        Text(
                          user!.jobTitle!,
                          style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          if (_error != null) ...[
            FormBanner.error(_error!),
            const SizedBox(height: 12),
          ],
          FormSection(
            title: l10n.account,
            children: [
              TextField(controller: _name, decoration: InputDecoration(labelText: l10n.name)),
              TextField(controller: _email, decoration: InputDecoration(labelText: l10n.email)),
              LoadingFilledButton(onPressed: _saveProfile, label: l10n.saveProfile, loading: _savingProfile),
            ],
          ),
          const SizedBox(height: 8),
          FormSection(
            title: l10n.security,
            children: [
              TextField(
                controller: _current,
                obscureText: true,
                decoration: InputDecoration(labelText: l10n.currentPassword),
              ),
              TextField(
                controller: _password,
                obscureText: true,
                decoration: InputDecoration(labelText: l10n.newPassword),
              ),
              TextField(
                controller: _confirm,
                obscureText: true,
                decoration: InputDecoration(labelText: l10n.confirmPassword),
              ),
              LoadingFilledButton(onPressed: _changePassword, label: l10n.changePassword, loading: _savingPassword),
            ],
          ),
          const SizedBox(height: 8),
          FormSection(
            title: l10n.preferences,
            children: [
              Text(l10n.language, style: Theme.of(context).textTheme.titleSmall),
              SegmentedButton<Locale>(
                segments: const [
                  ButtonSegment(value: Locale('fr'), label: Text('FR')),
                  ButtonSegment(value: Locale('en'), label: Text('EN')),
                ],
                selected: {locale},
                onSelectionChanged: (value) => ref.read(localeProvider.notifier).setLocale(value.first),
              ),
              Text(l10n.theme, style: Theme.of(context).textTheme.titleSmall),
              SegmentedButton<ThemeMode>(
                segments: [
                  ButtonSegment(value: ThemeMode.light, label: Text(l10n.light)),
                  ButtonSegment(value: ThemeMode.dark, label: Text(l10n.dark)),
                ],
                selected: {themeMode},
                onSelectionChanged: (value) => ref.read(themeModeProvider.notifier).setMode(value.first),
              ),
            ],
          ),
          const SizedBox(height: 16),
          OutlinedButton(
            onPressed: () => ref.read(authProvider.notifier).logout(),
            child: Text(l10n.logout),
          ),
        ],
      ),
    );
  }
}
