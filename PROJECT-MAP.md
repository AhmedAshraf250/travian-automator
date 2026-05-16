# Project Map

## Visual Flow

```mermaid
flowchart LR
    UI[Livewire Dashboard] --> Job[SyncTravianAccountJob]
    CLI[travian:automation-cycle] --> Job
    Job --> Sync[SyncAccountOverview]
    Job --> Auto[RunAccountAutomation]
    Sync --> Session[Account Session Contracts]
    Auto --> Session
    Session --> Guzzle[Guzzle Transport]
    Guzzle --> Travian[Travian Server]
    Travian --> Parser1[Dorf1 Parser]
    Travian --> Parser2[Dorf2 Parser]
    Parser1 --> Persist[PersistVillageOverview]
    Parser2 --> Persist
    Persist --> DB[(Database Snapshots)]
    DB --> UI
```

## Runtime Layers

```mermaid
flowchart TD
    A[Dashboard Layer] --> B[Application Layer]
    B --> C[Infrastructure Layer]
    B --> D[Persistence Layer]
    C --> E[External Travian HTTP]
    D --> A
```

## Isolation Map

```mermaid
flowchart LR
    Account[Account]
    Account --> Proxy[Proxy or Direct Connection]
    Account --> UA[User Agent]
    Account --> Cookies[CookieJar]
    Account --> Fingerprint[Transport Fingerprint]
    Account --> Villages[Village Snapshots]
```

## Settings Map

```mermaid
flowchart LR
    Program[Program Settings]
    Program --> Automation[automation_enabled]
    Program --> DefaultUA[default_user_agent]
    DefaultUA --> AccountUA{Account has custom UA?}
    AccountUA -->|Yes| Specific[Use account user agent]
    AccountUA -->|No| Fallback[Use global fallback user agent]
```

## Current Human Reading Summary

- `Dashboard` triggers sync, village updates, settings changes, and displays stored snapshots.
- `SyncTravianAccountJob` is the main queued entry point for both full-account and single-village work.
- `travian:automation-cycle` is a CLI shortcut that dispatches the same job.
- `Application` orchestrates login, download, parse, persist, and construction decisions.
- `Infrastructure` hides Guzzle and cookie mechanics.
- `Database` stores the latest account and village state.
- `Program settings` provide shared defaults without overriding per-account choices.
- `Dashboard live refresh` reads local Laravel state only and does not call Travian by itself.
