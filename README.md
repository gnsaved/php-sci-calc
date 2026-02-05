# Scientific Calculator

A calculator built with PHP. Supports basic and scientific operations.

## Why this architecture

I went with a separate `Calculator` and `History` class because:

1. **Calculator** - keeps all the math logic isolated. If I need to add new functions later (like hyperbolic trig or base conversions), I only touch one file. The `compute()` method returns an array with `ok` and either `value` or `error` instead of throwing exceptions everywhere - makes the controller cleaner.

2. **History with SQLite** - started with JSON but switched to SQLite because:
   - Can query by mode or date range later if needed
   - Handles concurrent writes better
   - Easier to add features like "favorite" calculations or search
   - Still just one file, no server setup needed

The classes use namespaces (`App\Core`, `App\Storage`) so it's easy to add autoloading later if the project grows.

## Setup

```bash
cd scientific-calculator
php -S localhost:8000
```

Open http://localhost:8000

Database creates itself on first run. If you want to start fresh:
```bash
rm database/calculator.db
```

## Structure

```
index.php         # main entry, handles form submission
src/
  Calculator.php  # math evaluation
  History.php     # sqlite storage
  BasicCalculator.png  # Basic Calculator Screenshot
  ScientificCalculator.png    # Scientific Calculator Screenshot
  
assets/
  main.css
  app.js
database/
  schema.sql      # reference schema
  calculator.db   # created automatically
```

## Features

- Basic: +, -, *, /, %
- Scientific: sin, cos, tan (and inverses), log, ln, sqrt, powers, factorial
- Trig uses degrees not radians
- History saved to SQLite
- Keyboard input works

## Notes

The calculator uses `eval()` for expression evaluation. For a production app you'd want a proper parser, but for a portfolio project this is fine. The input is sanitized to only allow numbers, operators, and known function names.

Error messages are meant to be readable ("Division by zero" not "Math domain error").
