# "Describe your PC" eval

40 hand-labelled descriptions (casual text, misspellings, laptops, dxdiag and System Information pastes, vague ones where the right answer is to leave the field empty, and one prompt-injection attempt), from `database/eval/pc-descriptions.json`. Run on 2026-10-05 with `gemini-flash-lite-latest`. Command: `php artisan ai:eval-specs`.

|  | rules | rules + AI |
|---|---|---|
| CPU right | 98% | 100% |
| GPU right | 85% | 100% |
| RAM right | 100% | 100% |
| all three right | 85% | 100% |
| wrong part selected | 0 | 0 |
| model asked | 0 of 40 | 12 of 40 |
| model call failed (rules used) | 0 | 0 |

The rules always run first. The model is only asked when a part is still missing, and its answer is used only if the catalogue has that part and the part's model number appears in the visitor's text. "Wrong part selected" counts answers that would pick the wrong CPU or GPU in the form; a field left empty is a plain miss.

## Misses: rules (7)

- #4 gpu: expected "NVIDIA GeForce GTX 1660 Super", got null
- #5 gpu: expected "NVIDIA GeForce GTX 1060", got null
- #6 gpu: expected "NVIDIA GeForce RTX 3060 Ti", got null
- #23 cpu: expected "AMD Ryzen 7 7800X3D", got null
- #23 gpu: expected "NVIDIA GeForce RTX 4070 Super", got null
- #25 gpu: expected "NVIDIA GeForce 9800 GT", got null
- #34 gpu: expected "NVIDIA GeForce RTX 4090", got null

## Misses: rules + AI (0)

None.
