#!/usr/bin/env python3
"""Mock du plugin SimBridge.xpl : émet les mêmes trames JSON (schéma v1) sur UDP 127.0.0.1:49180.

Simule un vol avec un épisode de stall warning (stall_warning=1) entre les
secondes 2 et 3, et un arrêt du moteur 2 en vol (eng_running=[1,0]) après 4 s,
pour valider les champs stall_ok / engine_cut_ok du vol de contrôle.

Usage : python3 mock_plugin.py [--seconds 10] [--port 49180] [--hz 10]
"""
import argparse
import json
import socket
import time


def frame(t: float, seq: int) -> dict:
    stall = 2.0 <= t <= 3.0
    engine_cut = t > 4.0
    return {
        "type": "simbridge",
        "v": 1,
        "ts_ms": int((time.time()) * 1000),
        "lat": 45.72777778 + seq * 1e-5,
        "lon": 4.94444444 + seq * 1e-5,
        "alt_m": 210.5 + (0.5 if t > 1 else 0.0),
        "alt_ft": 695.0,
        "ias_kt": 98.2 - (30.0 if stall else 0.0),   # le stall fait chuter la vitesse
        "tas_kt": 101.0,
        "gs_kt": 97.5,
        "vs_fpm": 0.0 if t <= 1 else 120.0,
        "hdg_true": 182.4,
        "pitch": 8.0 if stall else 0.5,
        "bank": -1.2,
        "magvar_deg": -1.8,
        "on_ground": 1 if t <= 1 else 0,
        "stall_warning": 1 if stall else 0,
        "overspeed_warning": 0,
        "crashed": 0,
        "fuel_total_kg": max(0.0, 750.5 - t * 0.3),
        "flaps_ratio": 0.0,
        "com1_mhz": 118.05,
        "squawk": 1234,
        "num_engines": 2,
        "eng_running": [1, 0] if engine_cut else [1, 1],
        "eng_rpm": [0.0, 0.0] if engine_cut else [2450.0, 2448.0],
        "eng_ff_kg_s": [0.0, 0.0] if engine_cut else [0.033, 0.033],
        "gear_deploy": 1.0 if t <= 1 else 0.0,
    }


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--seconds", type=float, default=10.0)
    ap.add_argument("--port", type=int, default=49180)
    ap.add_argument("--hz", type=float, default=10.0)
    args = ap.parse_args()

    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    dest = ("127.0.0.1", args.port)
    t0 = time.time()
    seq = 0
    while True:
        t = time.time() - t0
        if t > args.seconds:
            break
        payload = json.dumps(frame(t, seq), separators=(",", ":")).encode("utf-8")
        sock.sendto(payload, dest)
        seq += 1
        time.sleep(1.0 / args.hz)
    print(f"mock_plugin: {seq} trames émises vers {dest}")


if __name__ == "__main__":
    main()
