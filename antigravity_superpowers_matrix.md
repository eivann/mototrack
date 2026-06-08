# Antigravity & Superpowers Framework: Model Selection Matrix

This guide provides a comprehensive framework for selecting the optimal AI model within your Antigravity IDE based on the development phase and the layer of the application you are building. By aligning the right model to the right task, you maximize code quality, architectural integrity, and token efficiency.

---

## 🗺️ Part 1: Lifecycle Phases (The Workflow)

When executing tasks via the Superpowers framework, your development lifecycle transitions through distinct engineering phases. Use this matrix to switch models as you progress through your workflow.

### 1. Planning (The Architect)
* **Recommended Model:** `Claude Opus 4.6 (Thinking)` or `Claude Sonnet 4.6 (Thinking)`
* **Primary Focus:** Strategy, System Architecture, Edge-case Analysis, Blueprinting.
* **Why it matters:** Planning requires an AI that slows down to reason systematically before writing output. The **(Thinking)** models excel at inspecting your repository structures and generating comprehensive, placeholder-free implementation plans (e.g., your TDD step logs) without rushing or skipping crucial engineering contracts.

### 2. Execution / Coding (The Builder)
* **Recommended Model:** * `Claude Sonnet 4.6 (Thinking)` *(For Complex/Architectural Logic)*
  * `Gemini 3.5 Flash (High)` *(For Routine Layouts/Views)*
* **Primary Focus:** Writing clean, production-grade source code.
* **Why it matters:** * Use **Claude Sonnet** for core backend engine logic, service container bindings, or complex database operations where code shortcuts (`// TODO`) cannot be tolerated.
  * Switch to **Gemini 3.5 Flash** for repetitive boilerplate, basic controller methods, or simple routes to keep development fast and cost-effective.

### 3. Testing (The QA Engineer)
* **Recommended Model:** `Claude Sonnet 4.6 (Thinking)` or `Gemini 3.1 Pro (High)`
* **Primary Focus:** Mocking objects, writing Pest PHP or PHPUnit test suites, defining assertions.
* **Why it matters:** Writing reliable tests requires precise knowledge of assertion libraries and mock boundaries. **Claude Sonnet** writes accurate test code without hallucinating testing APIs, while **Gemini 3.1 Pro** is fantastic at identifying creative adversarial edge cases to stress-test your application.

### 4. Debugging (The Troubleshooter)
* **Recommended Model:** `Gemini 3.1 Pro (High)`
* **Primary Focus:** Stack trace parsing, analyzing application logs, memory dump investigations.
* **Why it matters:** Debugging is bounded by data collection. **Gemini 3.1 Pro** possesses an immense, industry-leading context window. You can paste massive multi-file exception trees or raw system log outputs directly into your chat panel, and it will pinpoint the exact culprit in seconds.

### 5. Refactoring (The Clean Code Expert)
* **Recommended Model:** `Claude Sonnet 4.6 (Thinking)`
* **Primary Focus:** Code deduplication, architectural optimization, formatting adjustments.
* **Why it matters:** Refactoring demands changing the structure of code without breaking its functional footprint (the "Blue" phase of TDD). **Claude Sonnet** holds a deep mastery of structural design patterns (e.g., Service-Repository, Interface Abstractions), ensuring your code evolves beautifully.

### 6. Finishing (The Publisher)
* **Recommended Model:** `Gemini 3.5 Flash (High)`
* **Primary Focus:** Inline docblocks, updating documentation (`README.md`/`CHANGELOG.md`), drafting semantic Git commits.
* **Why it matters:** Finishing consists of highly predictable tasks. You do not need expensive, heavy reasoning models for documentation extraction. **Gemini 3.5 Flash** can read your code diffs and instantly document your changes with extreme speed.

---

## 🏗️ Part 2: Application Layers (The Stack)

Depending on which specific directory of your project folder you are touching, you can optimize your model selection based on how it interacts with different tiers of your code stack.

```
┌────────────────────────────────────────────────────────┐
│ 🎨 FRONTEND LAYER                                      │
│ Models: Gemini 3.5 Flash (High / Medium)               │
│ Scope:  Blade Templates, Tailwind CSS, JavaScript UI   │
└───────────────────────────┬────────────────────────────┘
                            ▼
┌────────────────────────────────────────────────────────┐
│ ⚙️ MIDDLEWARE & ROUTING LAYER                          │
│ Models: Claude Sonnet 4.6 / Gemini 3.1 Pro             │
│ Scope:  Http Controllers, Routes, Request Validation   │
└───────────────────────────┬────────────────────────────┘
                            ▼
┌────────────────────────────────────────────────────────┐
│ 🗄️ BACKEND & ARCHITECTURE LAYER                         │
│ Models: Claude Opus 4.6 / Claude Sonnet 4.6            │
│ Scope:  Database Migrations, Interfaces, Providers     │
└────────────────────────────────────────────────────────┘
```

### 🎨 Frontend Layer
* **Target Files:** `resources/views/**/*.blade.php`, `resources/css/`, `resources/js/`
* **Best Choice:** `Gemini 3.5 Flash (High)` or `Gemini 3.5 Flash (Medium)`
* **Characteristics:** Rapid iteration, low architectural complexity, high focus on visual rendering/layouts. Flash models output code instantly, saving time during UI styling adjustments.

### ⚙️ Middlelayer
* **Target Files:** `app/Http/Controllers/`, `app/Http/Middleware/`, `routes/`
* **Best Choice:** `Claude Sonnet 4.6 (Thinking)` or `Gemini 3.1 Pro (High)`
* **Characteristics:** Managing HTTP request cycles, validating forms, managing route middleware, and bridging data payload to background engines. Requires balanced, framework-compliant logic.

### 🗄️ Backend Layer
* **Target Files:** `app/Services/`, `app/Providers/`, `database/migrations/`, Unit Tests
* **Best Choice:** `Claude Opus 4.6 (Thinking)` or `Claude Sonnet 4.6 (Thinking)`
* **Characteristics:** Designing abstract contracts (e.g., your `PaymentGatewayInterface`), resolving service bindings, setting up relational database structures, and strict test enforcement. Demands maximum context compliance.

---

## ⚡ Part 3: Quick Reference Cheat Sheet

| Task Objective | Active Mode | Recommended Model | Strategy |
| :--- | :--- | :--- | :--- |
| **Planning / Blueprinting** | Asking / Thinking | `Claude Opus 4.6 (Thinking)` | Formulates systematic TDD implementation logs. |
| **Architectural Coding** | Inline Execution | `Claude Sonnet 4.6 (Thinking)` | Enforces strict interface compliance without placeholding code. |
| **Routine / UI Coding** | Inline Execution | `Gemini 3.5 Flash (High)` | Fast, economical cycles for layout adjustments. |
| **Answering Questions** | General Chat | `Gemini 3.1 Pro (High)` | Broad framework exploration and ecosystem consults. |
| **Heavy Debugging** | Context Parsing | `Gemini 3.1 Pro (High)` | Swallows large stack traces and pinpoints faults instantly. |
| **Final Documentation** | File Updates | `Gemini 3.5 Flash (High)` | Autogenerates docblocks and commit patterns. |

---

## 🔄 The New Session Rule

Remember, every time you reboot your workstation or spin up a completely fresh Antigravity session, the chat resets its active memory pool. To ensure your selected model uses your framework rules correctly, **always supply this initialization prompt as your very first message**:

> *"Please look at my local superpowers repo at `C:\Users\Eivann\superpowers`. Read the instructions inside its `skills/` folder and adopt them as your core agentic workflows for this session."*