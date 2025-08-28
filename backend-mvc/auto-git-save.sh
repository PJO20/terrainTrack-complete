#!/bin/bash
while true; do
  git add .
  git commit -m "Sauvegarde automatique $(date '+%Y-%m-%d %H:%M:%S')" > /dev/null 2>&1
  sleep 300 # toutes les 5 minutes
  echo "[auto-git-save] Commit automatique effectué à $(date '+%H:%M:%S')"
done 