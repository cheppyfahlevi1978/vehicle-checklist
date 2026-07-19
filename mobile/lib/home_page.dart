import 'package:flutter/material.dart';
import 'api_client.dart';
import 'auth_service.dart';
import 'login_page.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});
  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  Map<String, dynamic>? dashboard;
  String? error;

  @override
  void initState() { super.initState(); load(); }

  Future<void> load() async {
    try {
      final dio = await ApiClient.instance.dio;
      final response = await dio.get('/dashboard');
      setState(() => dashboard = Map<String, dynamic>.from(response.data['data'] as Map));
    } catch (e) {
      setState(() => error = e.toString());
    }
  }

  Future<void> logout() async {
    await AuthService().logout();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => const LoginPage()), (_) => false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('WA-BOT'), actions: [IconButton(onPressed: logout, icon: const Icon(Icons.logout))]),
      body: RefreshIndicator(onRefresh: load, child: ListView(padding: const EdgeInsets.all(16), children: [
        if (error != null) Card(child: Padding(padding: const EdgeInsets.all(16), child: Text(error!, style: const TextStyle(color: Colors.red)))),
        if (dashboard == null) const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator())),
        if (dashboard != null) ...dashboard!.entries.map((e) => Card(child: ListTile(title: Text(e.key.replaceAll('_', ' ').toUpperCase()), trailing: Text('${e.value}', style: Theme.of(context).textTheme.headlineSmall)))),
        const SizedBox(height: 12),
        const Card(child: ListTile(leading: Icon(Icons.qr_code), title: Text('Perangkat WhatsApp'), subtitle: Text('QR, status koneksi, dan logout sesi tersedia melalui endpoint API perangkat.'))),
        const Card(child: ListTile(leading: Icon(Icons.message), title: Text('Kirim Pesan'), subtitle: Text('Pengiriman hanya untuk kontak yang telah memberikan persetujuan.'))),
      ])),
    );
  }
}
