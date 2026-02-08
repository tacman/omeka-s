# Jobs and Tasks Redesign

## Overview

The jobs/tasks system is shifting away from in-process task classes and toward an explicit queue-driven execution model. Jobs remain as persistent tracking records, while the work previously done in task classes moves into Symfony console commands executed by Messenger handlers.

## Major Design Changes

- **Tasks become console commands**: Task logic lives in `bin/console` commands that can be executed locally, in cron, or via Messenger.
- **Jobs become tracking-only records**: Jobs keep state, metadata, progress, and logs, but they no longer contain executable logic.
- **Execution moves to Messenger handlers**: Actual job work is performed in `Messenger/Handlers` using the job’s command name and payload.
- **Commands are the source of truth**: Any job that performs work should map directly to a command and be runnable outside the queue.

## Job Tracking Model

Jobs remain first-class entities for auditability, monitoring, and user feedback. The job record is responsible for:

- **Identity**: A stable ID used in messages and logs.
- **Lifecycle**: `queued`, `running`, `completed`, `failed`, `cancelled` (or equivalent) with timestamps for each transition.
- **Payload**: Command name plus normalized arguments/options (JSON-serializable).
- **Context**: User ID, site ID, or tenant info as needed for multi-tenant auditing.
- **Progress**: Percent complete and/or counters (items processed, errors, skipped, etc.).
- **Output**: Structured log output and error summaries for admin review.

Jobs are intentionally lightweight: they do not own the actual execution logic or domain services. Their role is to record intent and outcomes.

## Console Commands as Tasks

Console commands replace the previous task classes and are the canonical entry point for background work. Conventions:

- **Naming**: Use `app:*` naming and keep verbs explicit (e.g., `app:reindex-items`).
- **Single responsibility**: Each command encapsulates a single unit of work that can be called from CLI or Messenger.
- **Arguments/options**: All input should be CLI friendly and serialize cleanly into job payloads.
- **Idempotency**: Commands should be safe to retry when possible, matching Messenger retry behavior.

These commands can be triggered directly by developers or operators, making debugging and local iteration easier.

## Messenger Handlers (Execution Layer)

Messenger handlers in `Messenger/Handlers` run the actual job work and integrate with job tracking:

- **Message payload**: `jobId`, `commandName`, and `commandOptions` (or a dedicated DTO).
- **Handler flow**:
  1. Load the job record.
  2. Mark it as `running` and store start time.
  3. Execute the console command (or shared service) with the job payload.
  4. Update progress incrementally if available.
  5. Mark `completed` or `failed` with output/error details.
- **Error handling**: Failures should record a concise error message and any exception context needed for debugging.
- **Retries**: Messenger retry policies should align with command idempotency.

## End-to-End Flow

1. A user or system action creates a Job record with a command name and payload.
2. A Messenger message is dispatched with the `jobId` and command details.
3. The Messenger handler executes the command and updates the Job status.
4. The Job record becomes the authoritative source for progress and final outcome.

## Migration Guidance

- Replace each legacy task class with a Symfony console command.
- Create a corresponding Messenger handler that executes the command and updates the job.
- Ensure any UI or API entry points create a Job record and dispatch a message, rather than running logic inline.
- Keep shared business logic in services that can be invoked by both command and handler.
