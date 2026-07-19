import 'package:flutter/material.dart';
import 'auth_service.dart';
import 'home_page.dart';
import 'login_page.dart';

void main() => runApp(const EArsipApp());

class EArsipApp extends StatelessWidget {
  const EArsipApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'eArsip Mobile',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF1D4ED8)),
        useMaterial3: true,
      ),
      home: FutureBuilder<bool>(
        future: AuthService().hasSession(),
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Scaffold(body: Center(child: CircularProgressIndicator()));
          }
          return snapshot.data! ? const HomePage() : const LoginPage();
        },
      ),
    );
  }
}
