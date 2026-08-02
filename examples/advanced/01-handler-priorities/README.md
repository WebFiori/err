# Handler Priorities Example

This example demonstrates advanced handler priority management and execution order control.

## What This Example Shows

- Detailed priority system mechanics
- Handler execution order based on priority values
- Priority conflicts and resolution
- Registration order effects on same-priority handlers

## Key Concepts

1. **Priority Values**: Numeric priority system (higher = first)
2. **Execution Order**: Handlers execute from highest to lowest priority
3. **Priority Conflicts**: Same-priority handlers execute in registration order
4. **Handler Registration**: Order matters for equal priorities

## Files

- `00-shared-classes.php` - Base handler classes used by all examples
- `01-basic-priority-ordering.php` - Priority-based execution order
- `02-same-priority-handlers.php` - Same priority registration order

## Running the Examples

### Section 1: Basic Priority Ordering
```bash
php 01-basic-priority-ordering.php
```
Shows handlers executing from highest (1000) to lowest (0) priority.

### Section 2: Same Priority Handlers
```bash
php 02-same-priority-handlers.php
```

## Example Details

### Section 1: Basic Priority Ordering (`01-basic-priority-ordering.php`)

Demonstrates how handlers execute based on priority values:
- Critical Handler (Priority: 1000) - Executes first
- High Priority Handler (Priority: 100) - Executes second  
- Medium Priority Handler (Priority: 50) - Executes third
- Low Priority Handler (Priority: 10) - Executes fourth
- Default Handler (Priority: 0) - Executes last

**Key Learning**: Higher priority values execute before lower ones.

### Section 2: Same Priority Handlers (`02-same-priority-handlers.php`)

Shows registration order behavior when handlers have identical priorities:
- All handlers have priority 50
- Execution follows registration order: A → B → C

**Key Learning**: Registration order determines execution sequence for equal priorities.

### Shared Classes (`00-shared-classes.php`)

Contains the base `PriorityDemoHandler` class with:
- Execution counter tracking
- Priority display functionality
- Common handler interface implementation

## Priority System Rules

1. **Higher numbers execute first** (1000 before 100)
2. **Same priority = registration order** (first registered, first executed)
3. **Default priority is 0** if not explicitly set
4. **Negative priorities are allowed** and execute after positive ones

## Output Format

Each handler displays:
```
[execution_order] HandlerName (Priority: value) executed
```

Example:
```
[1] Critical (Priority: 1000) executed
[2] HighPriority (Priority: 100) executed
```
