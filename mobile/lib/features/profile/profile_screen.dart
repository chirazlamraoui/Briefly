import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_exception.dart';
import '../../l10n/app_localizations.dart';
import '../../providers/providers.dart';

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
  String? _message;
  String? _error;
  bool _saving = false;

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
      _saving = true;
      _error = null;
      _message = null;
    });

    try {
      await ref.read(apiClientProvider).updateProfile(name: _name.text.trim(), email: _email.text.trim());
      await ref.read(authProvider.notifier).refreshUser();
      if (mounted) {
        setState(() => _message = AppLocalizations.of(context)!.saveProfile);
      }
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } finally {
      if (mounted) {
        setState(() => _saving = false);
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
      _saving = true;
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
      setState(() => _message = l10n.changePassword);
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final locale = ref.watch(localeProvider);
    final themeMode = ref.watch(themeModeProvider);
    final user = ref.watch(authProvider).user;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.profile)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (user?.jobTitle != null) Text(user!.jobTitle!),
          const SizedBox(height: 12),
          TextField(controller: _name, decoration: InputDecoration(labelText: l10n.name)),
          const SizedBox(height: 12),
          TextField(controller: _email, decoration: InputDecoration(labelText: l10n.email)),
          const SizedBox(height: 16),
          FilledButton(onPressed: _saving ? null : _saveProfile, child: Text(l10n.saveProfile)),
          const SizedBox(height: 24),
          Text(l10n.changePassword, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 12),
          TextField(controller: _current, obscureText: true, decoration: InputDecoration(labelText: l10n.currentPassword)),
          const SizedBox(height: 12),
          TextField(controller: _password, obscureText: true, decoration: InputDecoration(labelText: l10n.newPassword)),
          const SizedBox(height: 12),
          TextField(controller: _confirm, obscureText: true, decoration: InputDecoration(labelText: l10n.confirmPassword)),
          const SizedBox(height: 16),
          FilledButton(onPressed: _saving ? null : _changePassword, child: Text(l10n.changePassword)),
          if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
          if (_message != null) Text(_message!),
          const SizedBox(height: 24),
          Text(l10n.language, style: Theme.of(context).textTheme.titleMedium),
          SegmentedButton<Locale>(
            segments: const [
              ButtonSegment(value: Locale('fr'), label: Text('FR')),
              ButtonSegment(value: Locale('en'), label: Text('EN')),
            ],
            selected: {locale},
            onSelectionChanged: (value) => ref.read(localeProvider.notifier).setLocale(value.first),
          ),
          const SizedBox(height: 16),
          Text(l10n.theme, style: Theme.of(context).textTheme.titleMedium),
          SegmentedButton<ThemeMode>(
            segments: [
              ButtonSegment(value: ThemeMode.light, label: Text(l10n.light)),
              ButtonSegment(value: ThemeMode.dark, label: Text(l10n.dark)),
            ],
            selected: {themeMode},
            onSelectionChanged: (value) => ref.read(themeModeProvider.notifier).setMode(value.first),
          ),
          const SizedBox(height: 24),
          OutlinedButton(
            onPressed: () => ref.read(authProvider.notifier).logout(),
            child: Text(l10n.logout),
          ),
        ],
      ),
    );
  }
}
