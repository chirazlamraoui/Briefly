import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api_exception.dart';
import '../../l10n/app_localizations.dart';
import '../../providers/providers.dart';
import '../../theme/briefly_theme.dart';
import '../../widgets/widgets.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
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
      await ref.read(authProvider.notifier).login(_email.text.trim(), _password.text);
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
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          children: [
            const SizedBox(height: 32),
            Align(
              alignment: Alignment.centerLeft,
              child: Container(
                width: 52,
                height: 52,
                decoration: BoxDecoration(
                  color: BrieflyColors.accent,
                  borderRadius: BorderRadius.circular(BrieflyRadii.md),
                ),
                child: const Icon(Icons.bolt_rounded, color: BrieflyColors.text, size: 28),
              ),
            ),
            const SizedBox(height: 20),
            Text(
              l10n.appName,
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 6),
            Text(
              l10n.signInHint,
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: scheme.onSurfaceVariant),
            ),
            const SizedBox(height: 28),
            BrieflyCard(
              padding: BrieflySpacing.form,
              child: AutofillGroup(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    TextField(
                      controller: _email,
                      keyboardType: TextInputType.emailAddress,
                      textInputAction: TextInputAction.next,
                      autofillHints: const [AutofillHints.email],
                      decoration: InputDecoration(labelText: l10n.email),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _password,
                      obscureText: true,
                      textInputAction: TextInputAction.done,
                      autofillHints: const [AutofillHints.password],
                      onSubmitted: (_) {
                        if (!_loading) {
                          _submit();
                        }
                      },
                      decoration: InputDecoration(labelText: l10n.password),
                    ),
                    if (_error != null) ...[
                      FormBanner.error(_error!),
                      const SizedBox(height: 12),
                    ],
                    LoadingFilledButton(onPressed: _submit, label: l10n.signIn, loading: _loading),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 8),
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
        padding: BrieflySpacing.page,
        children: [
          FormSection(
            children: [
              TextField(
                controller: _email,
                keyboardType: TextInputType.emailAddress,
                autofillHints: const [AutofillHints.email],
                decoration: InputDecoration(labelText: l10n.email),
              ),
              if (_error != null) FormBanner.error(_error!),
              if (_message != null) FormBanner.success(_message!),
              LoadingFilledButton(onPressed: _submit, label: l10n.sendResetLink, loading: _loading),
            ],
          ),
          const SizedBox(height: 8),
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
        padding: BrieflySpacing.page,
        children: [
          FormSection(
            children: [
              TextField(controller: _token, decoration: InputDecoration(labelText: l10n.resetToken)),
              TextField(
                controller: _email,
                keyboardType: TextInputType.emailAddress,
                decoration: InputDecoration(labelText: l10n.email),
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
              if (_error != null) FormBanner.error(_error!),
              LoadingFilledButton(onPressed: _submit, label: l10n.resetPassword, loading: _loading),
            ],
          ),
          const SizedBox(height: 8),
          TextButton(onPressed: () => context.go('/login'), child: Text(l10n.backToSignIn)),
        ],
      ),
    );
  }
}
