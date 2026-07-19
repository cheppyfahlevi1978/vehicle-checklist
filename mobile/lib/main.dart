import 'package:flutter/material.dart';
import 'auth_service.dart';
import 'home_page.dart';
import 'login_page.dart';

void main() => runApp(const WaBotApp());

class WaBotApp extends StatelessWidget {
  const WaBotApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'WA-BOT Mobile',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF128C7E)), useMaterial3: true),
      home: FutureBuilder<bool>(
        future: AuthService().hasSession(),
        builder: (context, snapshot) {
          if (!snapshot.hasData) return const Scaffold(body: Center(child: CircularProgressIndicator()));
          return snapshot.data! ? const HomePage() : const LoginPage();
        },
      ),
    );
  }
}
