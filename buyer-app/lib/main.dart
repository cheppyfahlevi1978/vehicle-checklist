import 'package:flutter/material.dart';
import 'auth_service.dart';
import 'home_page.dart';
import 'login_page.dart';

void main()=>runApp(const BuyerApp());
class BuyerApp extends StatelessWidget { const BuyerApp({super.key}); @override Widget build(BuildContext context)=>MaterialApp(title:'IAS Market Buyer',debugShowCheckedModeBanner:false,theme:ThemeData(colorScheme:ColorScheme.fromSeed(seedColor:Colors.indigo),useMaterial3:true),home:FutureBuilder<bool>(future:AuthService().hasSession(),builder:(c,s)=>!s.hasData?const Scaffold(body:Center(child:CircularProgressIndicator())):s.data!?const HomePage():const LoginPage(title:'IAS Market Buyer',role:'buyer',deviceName:'IAS Market Buyer Android'))); }
