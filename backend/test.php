<?php

$url = "https://wdoifaypyxdcenlitpsj.supabase.co/rest/v1/_products";
$key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Indkb2lmYXlweXhkY2VubGl0cHNqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODI0MzE4ODEsImV4cCI6MjA5ODAwNzg4MX0.Umm1oBw2WcQGXrPVswWzkmhRrVutrLKoZ1PSDYoa0Xg";

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $key",
    "Authorization: Bearer $key"
]);

echo curl_exec($ch);

curl_close($ch);

?>