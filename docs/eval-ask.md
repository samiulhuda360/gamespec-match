# Ask GameSpec eval

36 hand-labelled search requests (filters, "like ..." comparisons, runs-on-my-PC wording, one prompt-injection attempt), from `database/eval/search-queries.json`. Run on 2026-10-05 with `gemini-flash-lite-latest`. Command: `php artisan ai:eval-search`.

| field | rules | AI | AI + rules |
|---|---|---|---|
| genres | 100% | 100% | 100% |
| features | 94% | 100% | 100% |
| max_price | 97% | 100% | 100% |
| free_only | 97% | 100% | 100% |
| min_review_pct | 97% | 97% | 97% |
| released_after | 100% | 100% | 100% |
| released_before | 100% | 100% | 100% |
| runs_on | 97% | 100% | 100% |
| similar_to | 100% | 100% | 100% |
| sort | 100% | 100% | 100% |
| no_microtransactions | 100% | 100% | 100% |
| all fields right | 83% | 97% | 97% |
| model call failed (rules used) | 0 | 0 | 0 |

A field is right when it matches the label exactly, including staying empty when the request does not ask for it. "AI + rules" is what the app uses: the model's answer, with any field it left empty filled by the rules.

## Misses: rules (6)

- #31 features: expected ["local-co-op"], got ["co-op"]
- #32 max_price: expected 20, got null
- #33 runs_on: expected "minimum", got "any"
- #34 free_only: expected true, got false
- #35 features: expected ["online-co-op"], got []
- #36 min_review_pct: expected 90, got null

## Misses: AI (1)

- #10 min_review_pct: expected null, got 80

## Misses: AI + rules (1)

- #10 min_review_pct: expected null, got 80
