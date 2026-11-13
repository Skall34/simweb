#!/usr/bin/env python3
"""Test de boucle loopback du protocole SimBridge (sans X-Plane, sans C#).

Lance mock_plugin.py en thread, écoute 127.0.0.1:49180, valide le schéma v1
de PROTOCOL.md sur chaque trame, et vérifie que l'épisode de stall (stall_warning=1)
et l'arrêt moteur (eng_running=[1,0]) sont bien détectables — c'est exactement
ce qui alimente stall_ok / engine_cut_ok du vol de contrôle.

Usage : python3 test_loopback.py
Exit 0 = PASS, 1 = FAIL.
"""
import json
import socket
import sys
import threading
import time

import mock_plugin

PORT = 49180
REQUIRED = {
    "type": str, "v": (int, float), "ts_ms": (int, float),
    "lat": (int, float), "lon": (int, float), "alt_m": (int, float), "alt_ft": (int, float),
    "ias_kt": (int, float), "tas_kt": (int, float), "gs_kt": (int, float), "vs_fpm": (int, float),
    "hdg_true": (int, float), "pitch": (int, float), "bank": (int, float), "magvar_deg": (int, float),
    "on_ground": (int, float), "stall_warning": (int, float), "overspeed_warning": (int, float),
    "crashed": (int, float), "num_engines": (int, float),
    "eng_running": list, "eng_rpm": list, "eng_ff_kg_s": list,
    "fuel_total_kg": (int, float), "gear_deploy": (int, float), "flaps_ratio": (int, float),
    "com1_mhz": (int, float), "squawk": (int, float),
}


def main() -> int:
    frames = []
    stop = threading.Event()

    def sender():
        t0 = time.time()
        seq = 0
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        while not stop.is_set() and time.time() - t0 < 6.0:
            t = time.time() - t0
            payload = json.dumps(mock_plugin.frame(t, seq), separators=(",", ":")).encode()
            sock.sendto(payload, ("127.0.0.1", PORT))
            seq += 1
            time.sleep(0.1)

    th = threading.Thread(target=sender, daemon=True)
    th.start()

    rx = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    rx.bind(("127.0.0.1", PORT))
    rx.settimeout(2.0)
    t0 = time.time()
    while time.time() - t0 < 5.5:
        try:
            data, _ = rx.recvfrom(4096)
        except socket.timeout:
            break
        frames.append(json.loads(data.decode("utf-8")))
    stop.set()
    th.join(timeout=1.0)

    errors = []
    if not frames:
        errors.append("aucune trame reçue")
    for i, f in enumerate(frames):
        if f.get("type") != "simbridge":
            errors.append(f"[{i}] type != simbridge")
        for k, ty in REQUIRED.items():
            if k not in f:
                errors.append(f"[{i}] champ manquant: {k}")
            elif not isinstance(f[k], ty):
                errors.append(f"[{i}] champ {k} mauvais type: {type(f[k]).__name__}")
        if "num_engines" in f and "eng_running" in f:
            if len(f["eng_running"]) != int(f["num_engines"]):
                errors.append(f"[{i}] eng_running != num_engines")

    stalls = [f for f in frames if f.get("stall_warning") == 1]
    cuts = [f for f in frames if f.get("num_engines", 0) >= 2 and f.get("eng_running") == [1, 0]]
    if not stalls:
        errors.append("épisode stall_warning=1 non détecté")
    if not cuts:
        errors.append("épisode arrêt moteur eng_running=[1,0] non détecté")

    if errors:
        print("FAIL")
        for e in errors[:20]:
            print(" -", e)
        return 1
    print(f"PASS — {len(frames)} trames reçues, schéma v1 valide, "
          f"stall détecté sur {len(stalls)} trames, arrêt moteur sur {len(cuts)} trames")
    print("exemple :", json.dumps(frames[-1], separators=(",", ":"))[:300], "...")
    return 0


if __name__ == "__main__":
    sys.exit(main())
