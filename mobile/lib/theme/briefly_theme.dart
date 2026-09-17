import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// Zinc + rose colours used on every screen.
class BrieflyColors {
  static const accent = Color(0xFFE8B4BC);
  static const accentSoft = Color(0xFFFDF2F4);
  static const text = Color(0xFF18181B);
  static const textMuted = Color(0xFF71717A);
  static const background = Color(0xFFF4F4F5);
  static const surface = Color(0xFFFFFFFF);
  static const border = Color(0xFFE4E4E7);
  static const darkBackground = Color(0xFF09090B);
  static const darkSurface = Color(0xFF18181B);
  static const darkBorder = Color(0xFF27272A);
}

class BrieflySpacing {
  static const page = EdgeInsets.fromLTRB(16, 12, 16, 24);
  static const pageWithFab = EdgeInsets.fromLTRB(16, 12, 16, 88);
  static const card = EdgeInsets.all(16);
  static const form = EdgeInsets.fromLTRB(20, 16, 20, 20);
}

class BrieflyRadii {
  static const sm = 12.0;
  static const md = 16.0;
  static const lg = 20.0;
  static const pill = 999.0;
}

class BrieflyTheme {
  static ThemeData light() {
    const scheme = ColorScheme(
      brightness: Brightness.light,
      primary: BrieflyColors.text,
      onPrimary: Color(0xFFFAFAFA),
      primaryContainer: Color(0xFFE4E4E7),
      onPrimaryContainer: BrieflyColors.text,
      secondary: Color(0xFFB76E79),
      onSecondary: Color(0xFFFAFAFA),
      secondaryContainer: BrieflyColors.accentSoft,
      onSecondaryContainer: Color(0xFF3F272B),
      tertiary: BrieflyColors.accent,
      onTertiary: BrieflyColors.text,
      tertiaryContainer: BrieflyColors.accentSoft,
      onTertiaryContainer: Color(0xFF3F272B),
      error: Color(0xFFB91C1C),
      onError: Color(0xFFFFFFFF),
      errorContainer: Color(0xFFFEE2E2),
      onErrorContainer: Color(0xFF7F1D1D),
      surface: BrieflyColors.surface,
      onSurface: BrieflyColors.text,
      onSurfaceVariant: BrieflyColors.textMuted,
      outline: Color(0xFFD4D4D8),
      outlineVariant: BrieflyColors.border,
      surfaceContainerLowest: Color(0xFFFFFFFF),
      surfaceContainerLow: BrieflyColors.background,
      surfaceContainer: Color(0xFFEEEEF0),
      surfaceContainerHigh: Color(0xFFE4E4E7),
      surfaceContainerHighest: Color(0xFFD4D4D8),
      inverseSurface: BrieflyColors.darkSurface,
      onInverseSurface: Color(0xFFFAFAFA),
      inversePrimary: BrieflyColors.accent,
      scrim: Color(0xFF000000),
      shadow: Color(0xFF000000),
    );

    return _apply(
      ThemeData(
        useMaterial3: true,
        brightness: Brightness.light,
        colorScheme: scheme,
        scaffoldBackgroundColor: BrieflyColors.background,
      ),
    );
  }

  static ThemeData dark() {
    const scheme = ColorScheme(
      brightness: Brightness.dark,
      primary: BrieflyColors.accent,
      onPrimary: BrieflyColors.text,
      primaryContainer: Color(0xFF3F272B),
      onPrimaryContainer: BrieflyColors.accentSoft,
      secondary: BrieflyColors.accent,
      onSecondary: BrieflyColors.text,
      secondaryContainer: Color(0xFF3F272B),
      onSecondaryContainer: BrieflyColors.accentSoft,
      tertiary: BrieflyColors.accent,
      onTertiary: BrieflyColors.text,
      tertiaryContainer: Color(0xFF3F272B),
      onTertiaryContainer: BrieflyColors.accentSoft,
      error: Color(0xFFFCA5A5),
      onError: Color(0xFF7F1D1D),
      errorContainer: Color(0xFF7F1D1D),
      onErrorContainer: Color(0xFFFEE2E2),
      surface: BrieflyColors.darkSurface,
      onSurface: Color(0xFFFAFAFA),
      onSurfaceVariant: Color(0xFFA1A1AA),
      outline: Color(0xFF3F3F46),
      outlineVariant: BrieflyColors.darkBorder,
      surfaceContainerLowest: Color(0xFF050506),
      surfaceContainerLow: BrieflyColors.darkBackground,
      surfaceContainer: Color(0xFF1C1C1F),
      surfaceContainerHigh: Color(0xFF27272A),
      surfaceContainerHighest: Color(0xFF3F3F46),
      inverseSurface: Color(0xFFFAFAFA),
      onInverseSurface: BrieflyColors.text,
      inversePrimary: BrieflyColors.text,
      scrim: Color(0xFF000000),
      shadow: Color(0xFF000000),
    );

    return _apply(
      ThemeData(
        useMaterial3: true,
        brightness: Brightness.dark,
        colorScheme: scheme,
        scaffoldBackgroundColor: BrieflyColors.darkBackground,
      ),
    );
  }

  static ThemeData _apply(ThemeData base) {
    final scheme = base.colorScheme;
    final textTheme = GoogleFonts.plusJakartaSansTextTheme(base.textTheme).apply(
      bodyColor: scheme.onSurface,
      displayColor: scheme.onSurface,
    );
    final radius = BorderRadius.circular(BrieflyRadii.sm);
    final cardRadius = BorderRadius.circular(BrieflyRadii.md);

    return base.copyWith(
      textTheme: textTheme,
      splashFactory: InkRipple.splashFactory,
      appBarTheme: AppBarTheme(
        backgroundColor: scheme.surface,
        foregroundColor: scheme.onSurface,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 1,
        centerTitle: false,
        titleTextStyle: textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700, fontSize: 20),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: scheme.surface,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        border: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: scheme.outlineVariant)),
        enabledBorder: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: scheme.outlineVariant)),
        focusedBorder: OutlineInputBorder(
          borderRadius: radius,
          borderSide: const BorderSide(color: BrieflyColors.accent, width: 1.6),
        ),
        errorBorder: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: scheme.error)),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: radius,
          borderSide: BorderSide(color: scheme.error, width: 1.6),
        ),
        labelStyle: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
        floatingLabelStyle: textTheme.bodySmall?.copyWith(color: scheme.onSurface, fontWeight: FontWeight.w600),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size.fromHeight(48),
          textStyle: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w700),
          shape: RoundedRectangleBorder(borderRadius: radius),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(48),
          foregroundColor: scheme.onSurface,
          textStyle: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w700),
          side: BorderSide(color: scheme.outline),
          shape: RoundedRectangleBorder(borderRadius: radius),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: scheme.onSurface,
          textStyle: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w600),
        ),
      ),
      cardTheme: CardThemeData(
        color: scheme.surface,
        elevation: 0,
        margin: EdgeInsets.zero,
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(
          borderRadius: cardRadius,
          side: BorderSide(color: scheme.outlineVariant),
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: scheme.surfaceContainerLow,
        selectedColor: scheme.secondaryContainer,
        disabledColor: scheme.surfaceContainer,
        labelStyle: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w600),
        secondaryLabelStyle: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w600),
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
        shape: const StadiumBorder(),
        side: BorderSide(color: scheme.outlineVariant),
        showCheckmark: false,
      ),
      listTileTheme: ListTileThemeData(
        iconColor: scheme.onSurfaceVariant,
        textColor: scheme.onSurface,
        selectedTileColor: scheme.secondaryContainer,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 2),
        titleTextStyle: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
        subtitleTextStyle: textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        elevation: 1,
        backgroundColor: scheme.inverseSurface,
        contentTextStyle: textTheme.bodyMedium?.copyWith(color: scheme.onInverseSurface),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(BrieflyRadii.sm)),
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: scheme.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(BrieflyRadii.lg)),
        titleTextStyle: textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: scheme.primary,
        foregroundColor: scheme.onPrimary,
        elevation: 2,
        highlightElevation: 3,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(BrieflyRadii.md)),
      ),
      progressIndicatorTheme: ProgressIndicatorThemeData(
        color: BrieflyColors.accent,
        linearTrackColor: scheme.surfaceContainerHighest,
        circularTrackColor: scheme.surfaceContainerHighest,
      ),
      dividerTheme: DividerThemeData(color: scheme.outlineVariant, space: 1, thickness: 1),
      checkboxTheme: CheckboxThemeData(
        fillColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return scheme.primary;
          }
          return Colors.transparent;
        }),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
      ),
      segmentedButtonTheme: SegmentedButtonThemeData(
        style: ButtonStyle(
          visualDensity: VisualDensity.standard,
          textStyle: WidgetStatePropertyAll(textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w600)),
          backgroundColor: WidgetStateProperty.resolveWith((states) {
            if (states.contains(WidgetState.selected)) {
              return scheme.secondaryContainer;
            }
            return scheme.surface;
          }),
          foregroundColor: WidgetStatePropertyAll(scheme.onSurface),
          side: WidgetStatePropertyAll(BorderSide(color: scheme.outlineVariant)),
        ),
      ),
      dropdownMenuTheme: DropdownMenuThemeData(
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: scheme.surface,
          border: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: scheme.outlineVariant)),
        ),
      ),
      iconTheme: IconThemeData(color: scheme.onSurfaceVariant),
    );
  }
}
