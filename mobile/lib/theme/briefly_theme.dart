import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class BrieflyColors {
  static const accent = Color(0xFFE8B4BC);
  static const accentSoft = Color(0xFFFDF2F4);
  static const text = Color(0xFF18181B);
  static const textMuted = Color(0xFF71717A);
  static const background = Color(0xFFF7F7F8);
  static const surface = Color(0xFFFFFFFF);
  static const border = Color(0xFFECECEF);
  static const darkBackground = Color(0xFF09090B);
  static const darkSurface = Color(0xFF18181B);
  static const darkBorder = Color(0xFF27272A);
}

class BrieflyTheme {
  static ThemeData light() {
    final base = ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      colorScheme: ColorScheme.fromSeed(
        seedColor: BrieflyColors.text,
        brightness: Brightness.light,
        primary: BrieflyColors.text,
        secondary: BrieflyColors.accent,
        surface: BrieflyColors.surface,
      ),
      scaffoldBackgroundColor: BrieflyColors.background,
    );

    return _apply(base, BrieflyColors.border);
  }

  static ThemeData dark() {
    final base = ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: ColorScheme.fromSeed(
        seedColor: BrieflyColors.accent,
        brightness: Brightness.dark,
        primary: BrieflyColors.accent,
        secondary: BrieflyColors.accent,
        surface: BrieflyColors.darkSurface,
      ),
      scaffoldBackgroundColor: BrieflyColors.darkBackground,
    );

    return _apply(base, BrieflyColors.darkBorder);
  }

  static ThemeData _apply(ThemeData base, Color border) {
    final textTheme = GoogleFonts.plusJakartaSansTextTheme(base.textTheme);

    return base.copyWith(
      textTheme: textTheme,
      appBarTheme: AppBarTheme(
        backgroundColor: base.colorScheme.surface,
        foregroundColor: base.colorScheme.onSurface,
        elevation: 0,
        scrolledUnderElevation: 0,
        titleTextStyle: textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: base.colorScheme.surface,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: border)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: border)),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: BrieflyColors.accent, width: 1.4),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size.fromHeight(48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      cardTheme: CardThemeData(
        color: base.colorScheme.surface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: border),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        height: 68,
        elevation: 0,
        backgroundColor: base.colorScheme.surface,
        indicatorColor: BrieflyColors.accentSoft,
        labelPadding: const EdgeInsets.only(top: 2, bottom: 0),
        labelTextStyle: WidgetStatePropertyAll(
          textTheme.labelSmall?.copyWith(fontSize: 11, fontWeight: FontWeight.w600, height: 1.1),
        ),
      ),
    );
  }
}
