import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api_exception.dart';
import '../../l10n/app_localizations.dart';
import '../../providers/providers.dart';
import '../../theme/briefly_theme.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _remember = true;
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final l10n = AppLocalizations.of(context)!;
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await ref.read(authProvider.notifier).login(_email.text.trim(), _password.text, remember: _remember);
    } on ApiException catch (error) {
      setState(() => _error = error.firstFieldError('email') ?? error.message);
    } catch (_) {
      setState(() => _error = l10n.invalidCredentials);
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 48),
            Text(l10n.appName, style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w700)),
            const SizedBox(height: 8),
            Text(l10n.signIn, style: Theme.of(context).textTheme.titleMedium?.copyWith(color: BrieflyColors.textMuted)),
            const SizedBox(height: 32),
            TextField(
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              decoration: InputDecoration(labelText: l10n.email),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _password,
              obscureText: true,
              decoration: InputDecoration(labelText: l10n.password),
            ),
            CheckboxListTile(
              value: _remember,
              onChanged: (value) => setState(() => _remember = value ?? true),
              title: Text(l10n.rememberMe),
              contentPadding: EdgeInsets.zero,
            ),
            if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: _loading ? null : _submit,
              child: _loading ? const CircularProgressIndicator() : Text(l10n.signIn),
            ),
            TextButton(
              onPressed: () => context.push('/forgot-password'),
              child: Text(l10n.forgotPassword),
            ),
          ],
        ),
      ),
    );
  }
}

class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  ConsumerState<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends ConsumerState<ForgotPasswordScreen> {
  final _email = TextEditingController();
  bool _loading = false;
  String? _message;
  String? _error;

  @override
  void dispose() {
    _email.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final l10n = AppLocalizations.of(context)!;
    setState(() {
      _loading = true;
      _error = null;
      _message = null;
    });

    try {
      await ref.read(apiClientProvider).forgotPassword(_email.text.trim());
      setState(() => _message = l10n.resetLinkSent);
    } on ApiException catch (error) {
      setState(() => _error = error.firstFieldError('email') ?? error.message);
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.forgotPassword)),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          TextField(controller: _email, decoration: InputDecoration(labelText: l10n.email)),
          const SizedBox(height: 16),
          if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
          if (_message != null) Text(_message!),
          const SizedBox(height: 12),
          FilledButton(onPressed: _loading ? null : _submit, child: Text(l10n.sendResetLink)),
          TextButton(onPressed: () => context.push('/reset-password'), child: Text(l10n.resetPassword)),
        ],
      ),
    );
  }
}

class ResetPasswordScreen extends ConsumerStatefulWidget {
  const ResetPasswordScreen({super.key});

  @override
  ConsumerState<ResetPasswordScreen> createState() => _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends ConsumerState<ResetPasswordScreen> {
  final _token = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _token.dispose();
    _email.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
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
      _loading = true;
      _error = null;
    });

    try {
      await ref.read(apiClientProvider).resetPassword(
            token: _token.text.trim(),
            email: _email.text.trim(),
            password: _password.text,
            passwordConfirmation: _confirm.text,
          );
      if (mounted) {
        context.go('/login');
      }
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.resetPassword)),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          TextField(controller: _token, decoration: InputDecoration(labelText: l10n.resetToken)),
          const SizedBox(height: 12),
          TextField(controller: _email, decoration: InputDecoration(labelText: l10n.email)),
          const SizedBox(height: 12),
          TextField(controller: _password, obscureText: true, decoration: InputDecoration(labelText: l10n.newPassword)),
          const SizedBox(height: 12),
          TextField(controller: _confirm, obscureText: true, decoration: InputDecoration(labelText: l10n.confirmPassword)),
          const SizedBox(height: 16),
          if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
          FilledButton(onPressed: _loading ? null : _submit, child: Text(l10n.resetPassword)),
          TextButton(onPressed: () => context.go('/login'), child: Text(l10n.backToSignIn)),
        ],
      ),
    );
  }
}
