# Hooks git versionnes (simweb)

Installation une fois par clone :

```
git config core.hooksPath githooks
```

## pre-push

Avant chaque push (branche **ou tag**), lance `simbridge/tests/run_all_tests.sh`
(suites C# du client SimBridge + loopback UDP du protocole + lint PHP de
`api/` et `includes/`). Si un test echoue, le push est annulé.
Bypass exceptionnel : `git push --no-verify`.

La meme suite tourne en CI dans `.github/workflows/test.yml`.
