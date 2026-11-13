#!/usr/bin/env python3
"""Écoute les trames SimBridge (UDP 127.0.0.1:49180) et les affiche en direct.

Usage : python3 listen.py [--port 49180] [--count 20]
Utile avec X-Plane + SimBridge.xpl installés, ou avec mock_plugin.py pour tester.
"""
import argparse
import json
import socket


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--port", type=int, default=49180)
    ap.add_argument("--count", type=int, default=0, help="0 = infini")
    args = ap.parse_args()

    rx = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    rx.bind(("127.0.0.1", args.port))
    rx.settimeout(1.0)
    print(f"Écoute UDP 127.0.0.1:{args.port} — Ctrl+C pour arrêter")
    n = 0
    while args.count == 0 or n < args.count:
        try:
            data, _ = rx.recvfrom(4096)
        except socket.timeout:
            continue
        n += 1
        try:
            f = json.loads(data.decode("utf-8"))
            print(f"[{n}] stall={f.get('stall_warning')} on_ground={f.get('on_ground')} "
                  f"ias={f.get('ias_kt'):.1f}kt alt={f.get('alt_ft'):.0f}ft "
                  f"lat={f.get('lat'):.5f} lon={f.get('lon'):.5f} "
                  f"eng={f.get('eng_running')} fuel={f.get('fuel_total_kg'):.0f}kg")
        except Exception:
            print(f"[{n}] trame non JSON ({len(data)} o)")


if __name__ == "__main__":
    main()
