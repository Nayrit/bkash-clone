# bKash Clone: Mobile Financial Services (MFS) Technical Documentation & Architecture Specification

## 1. System Overview
This document details the complete technical implementation of our enterprise-grade Mobile Financial Services (MFS) platform (**bKash Clone**). The system facilitates digital currency transfers between three distinct actors. It operates on a **Central Treasury Model**, meaning all digital currency originates from the System Administrator and is distributed into the ecosystem via Agents. 

To mirror real-life MFS operational mechanics accurately, the platform tracks three distinct financial pillars for Agents:
1. **Digital Float Balance (`balance`)**: Electronic e-money used to transfer funds digitally.
2. **Physical Cash Collected (`cash_in_hand`)**: Real paper currency collected or disbursed across physical counter transactions.
3. **Payable Due to Admin (`admin_due`)**: Credit exposure and collection share owed back to the Central Treasury.

The architecture prioritizes ACID-compliant database operations, strict role-based routing, pessimistic row locking, and dynamic system variables.

---

## 2. System Roles & Access Control Matrix

| Role (Alias) | Primary Function | Key Features & Responsibilities | Treasury Impact |
| :--- | :--- | :--- | :--- |
| **System Administrator (`admin`)** | System-wide financial oversight and operational control. | • Global ledger and real-time transaction oversight across all accounts.<br>• Dynamic fee and commission configuration.<br>• Approve/reject Agent float funding requests.<br>• Complete user, agent, and system float management.<br>• Interactive modal breakdowns for every ledger entry. | Holds the master system float (`৳ 100,000.00` baseline). All platform revenue (Send Money flat fees and Cash-Out treasury shares) routes directly to this account. |
| **Agent (`agent`)** | Serves as the physical bridge between paper cash and digital e-money. | • Request e-money float from Admin Treasury on credit.<br>• Execute Customer Cash-In (Deposit).<br>• Process Customer Cash-Out (Withdrawal).<br>• Step-by-step partial or full settlement of outstanding Admin dues.<br>• Live tracking of Digital Float, Hand Cash, and Admin Dues. | Receives digital float from Admin on credit (`admin_due` increases). Collects physical paper cash during Cash-In and disburses hand cash during Cash-Out. Earns automated Cash-Out commission (`1.5%`). |
| **Customer (`customer`)** | Executes day-to-day personal digital financial transactions. | • P2P Send Money (Customer to Customer).<br>• Initiate Cash-Out (Customer to Agent).<br>• Receive Cash-In (Agent to Customer).<br>• View personal balance and transaction statements with pop-up details. | Generates system revenue by paying calculated fees on outgoing transactions (Flat `৳ 5.00` on Send Money; `2.0%` on Cash-Out). |

---

## 3. Database Schema (Physical Data Model)
The database is highly normalized to separate user identity from financial state and transaction history. All financial values strictly utilize fixed-precision decimal data types.

### A. `users` Table
Handles identity, authentication, and Role-Based Access Control (RBAC).
* `id` (`BIGINT`, Primary Key)
* `name` (`VARCHAR`)
* `phone` (`VARCHAR`, Unique, Indexed) — Primary identifier for MFS operations.
* `password` (`VARCHAR`, Hashed via bcrypt)
* `role` (`ENUM`: `'admin'`, `'agent'`, `'customer'`)
* `timestamps`

### B. `wallets` Table
Maintains the liquid financial state of an account, including operational physical cash and credit exposure.
* `id` (`BIGINT`, Primary Key)
* `user_id` (`BIGINT`, Foreign Key -> `users.id`, Cascade Delete)
* `balance` (`DECIMAL(15,2)`, Default: `0.00`) — Liquid digital e-money float. Floating-point data types (`FLOAT`/`DOUBLE`) are strictly prohibited to prevent binary rounding errors.
* `cash_in_hand` (`DECIMAL(15,2)`, Default: `0.00`) — Physical paper cash collected or disbursed across physical counter operations.
* `admin_due` (`DECIMAL(15,2)`, Default: `0.00`) — Cumulative outstanding debt owed to Admin Treasury (from float funding distributions).
* `timestamps`

### C. `transactions` Table (The Immutable Ledger)
An append-only double-entry audit table recording every movement of digital currency and revenue splits.
* `id` (`BIGINT`, Primary Key)
* `txn_id` (`VARCHAR`, Unique, Indexed) — Cryptographic/alphanumeric reference hash (e.g., `TXN_6a51cf4364919`).
* `type` (`ENUM`: `'send_money'`, `'cash_in'`, `'cash_out'`, `'funding'`, `'commission'`, `'system_float'`)
* `sender_id` (`BIGINT`, Foreign Key -> `users.id`)
* `receiver_id` (`BIGINT`, Foreign Key -> `users.id`)
* `amount` (`DECIMAL(15,2)`) — The base transfer principal value.
* `fee` (`DECIMAL(15,2)`) — Total customer fee charged.
* `agent_commission` (`DECIMAL(15,2)`) — Agent's earned share of the transaction fee.
* `admin_fee` (`DECIMAL(15,2)`) — Admin Treasury's net platform revenue share.
* `created_at`, `updated_at` (`TIMESTAMP`)

### D. `agent_funding_requests` Table
Asynchronous tracking for Agents requesting e-money float from the Treasury.
* `id` (`BIGINT`, Primary Key)
* `agent_id` (`BIGINT`, Foreign Key -> `users.id`)
* `amount` (`DECIMAL(15,2)`)
* `status` (`ENUM`: `'pending'`, `'approved'`, `'rejected'`)
* `timestamps`

### E. `system_settings` Table
Allows dynamic adjustment of operational variables without code redeployment.
* `key` (`VARCHAR`, Unique) — e.g., `'cash_out_fee_percentage'`, `'agent_commission_percentage'`, `'cash_in_commission_percentage'`.
* `value` (`VARCHAR`) — Numeric string configurations.

---

## 4. Core Transaction Algorithms & Real-Life Operational Cashflow

Financial transfers are executed inside atomic database transactions (`DB::transaction()`) with pessimistic row locking (`lockForUpdate()`) to ensure complete data consistency.

### Algorithm 1: Agent Funding Request (Float Distribution)
1. **Request**: Agent submits float request for `$Amount`.
2. **Approval & Locking**: Admin approves request; system locks Admin and Agent wallet rows.
3. **State Mutation**:
   * Decrement Admin Treasury Digital Float (`balance`) by `$Amount`.
   * Increment Agent Digital Float (`balance`) by `$Amount`.
   * **Increment Agent Payable Due to Admin (`admin_due`)** by `$Amount` (since float is distributed on credit).
4. **Ledger Entry**: Create immutable record (`type = 'system_float'`).

### Algorithm 2: Customer Cash-In (Paper Cash -> Digital Deposit)
* **Fee Structure**: `৳ 0.00` Fee / `0.00%` Commission (100% free deposit).
1. **Pre-Flight Validation**: Validate Customer phone exists and verify Agent has sufficient Digital Float (`$agentWallet->balance >= $Amount`).
2. **Lock Acquisition**: Pessimistically lock Agent and Customer wallet rows.
3. **State Mutation**:
   * Decrement Agent Digital Float (`balance`) by `$Amount`.
   * Increment Customer Digital Float (`balance`) by `$Amount`.
   * **Increment Agent Physical Hand Cash (`cash_in_hand`)** by `$Amount` (Agent received physical paper cash).
   * *(Note: `admin_due` remains unchanged since the Agent already owed Admin when they received the initial float).*
4. **Ledger Entry**: Record `type = 'cash_in'` with `fee = 0.00`, `agent_commission = 0.00`.

### Algorithm 3: P2P Send Money (Customer to Customer)
* **Fee Structure**: Flat **`৳ 5.00`** fee on every transfer routed directly to Admin Treasury.
1. **Pre-Flight Validation**: Verify sender balance $\ge (Amount + 5.00)$. Prevent self-transfers.
2. **Lock Acquisition**: Pessimistically lock Sender, Receiver, and Admin Treasury wallet rows.
3. **State Mutation**:
   * Decrement Sender Digital Float (`balance`) by $(Amount + 5.00)$.
   * Increment Receiver Digital Float (`balance`) by $Amount$.
   * **Increment Admin Treasury (`balance`)** by $5.00$.
4. **Ledger Entry**: Record `type = 'send_money'` with `fee = 5.00`, `admin_fee = 5.00`.

### Algorithm 4: Customer Cash-Out to Agent (Digital Withdrawal -> Paper Cash)
* **Fee Structure**: `2.0%` Total Fee (`৳ 20 per ৳ 1,000`). Split: **`1.5%` (`৳ 15`) to Agent**, **`0.5%` (`৳ 5`) to Admin**.
1. **Request Initiation**: Customer submits Agent Phone Number and Amount.
2. **Pre-Flight Validation**: System validates Agent exists and verifies Customer's current balance $\ge (Amount + Fee)$.
3. **Lock Acquisition (Concurrency Control)**: System initiates `DB::transaction()` and applies pessimistic locking (`lockForUpdate()`) on Customer, Agent, and Treasury wallet rows to prevent race conditions (double-spending).
4. **State Mutation**:
   * Decrement Customer Digital Float (`balance`) by $(Amount + Fee)$.
   * Increment Agent Digital Float (`balance`) by $(Amount + AgentCommission)$.
   * **Decrement Agent Physical Hand Cash (`cash_in_hand`)** by $Amount$ (Agent hands physical paper cash out of drawer to Customer).
   * Increment Admin Treasury Digital Float (`balance`) by $AdminRevenue$.
5. **Ledger Entry**: Insert row into `transactions` documenting sender, receiver, base amount, total fee, agent commission, and admin treasury fee.
6. **Commit**: Transaction commits to database. If any step fails, the entire block rolls back.

### Algorithm 5: Step-by-Step Settlement with Admin Treasury
1. **Flexible Payment**: Agent submits any partial or full payment amount `$Amount` towards their outstanding `admin_due`.
2. **State Mutation**:
   * Increment Admin Treasury Digital Float (`balance`) by `$Amount`.
   * Decrement Agent Payable Due (`admin_due`) by `min($Amount, $agentWallet->admin_due)`.
   * Decrement Agent Physical Hand Cash (`cash_in_hand`) by `min($Amount, $agentWallet->cash_in_hand)`.

---

## 5. Security & Risk Mitigation Strategy

| Security Pillar | Threat / Vulnerability | Technical Mitigation Strategy |
| :--- | :--- | :--- |
| **Race Conditions / Double Spending** | Concurrent duplicate requests (e.g., user double-clicks submit within milliseconds). | Mitigated using Database Row-Level Locking (`lockForUpdate()`). The database serializes simultaneous transactions on the same wallet row, causing subsequent requests to wait and re-evaluate balance constraints before executing. |
| **Data Orphanage (Partial Updates)** | Network failure or exception occurring midway through a debit/credit sequence. | Mitigated strictly via Laravel's `DB::transaction()` closures. A deduction will never commit to persistent storage if any subsequent credit or audit log insertion fails. |
| **Floating-Point Precision Loss** | IEEE 754 floating-point rounding anomalies (`0.1 + 0.2 != 0.3`). | Mitigated by utilizing fixed-precision **`DECIMAL(15,2)`** column types exclusively across all financial schemas (`wallets`, `transactions`, `agent_funding_requests`). |
| **Unauthorized Elevation** | Customer or Agent attempting to hit administrative API endpoints. | Mitigated by strict Route Middleware (`auth`, `role:admin`, `role:agent`, `role:customer`) enforced at the HTTP router layer (`routes/web.php`). |

---

## 6. Implementation Lifecycle & Delivered Enhancements

### Day 1: Infrastructure & Auth
* Provisioned local development environment (Laragon / MAMP / MySQL).
* Executed normalized database migrations for identity, roles, and schema tables.
* Established Laravel authentication and strict Role-Based Middleware boundaries (`role:admin`, `role:agent`, `role:customer`).

### Day 2: State Management & Treasury Ops
* Configured Eloquent Model relationships (`User hasOne Wallet`, `User hasMany Transactions`).
* Developed automatic registration listeners to provision zero-balance wallets upon user signup.
* Built the Admin Treasury panel and the Agent Funding Request workflow.

### Day 3: The Financial Engine
* Implemented ACID transactional logic for Perform Cash-In, Send Money P2P, and Customer Cash-Out.
* Enforced pessimistic row-level locking (`lockForUpdate()`) across all wallet updates.
* Incorporated dynamic system setting lookups for fee percentages and revenue splits.

### Day 4: Interactive Interface, Operational Ledgers & Comprehensive QA
* Built role-specific dashboards with rich aesthetic UI cards and responsive grids.
* Implemented interactive **Modal Dialog Pop-Up Windows** on every dashboard ledger table, allowing users to inspect complete transaction breakdowns (Principal, Fee, Commission, Counterparty details) with a single row click.
* Delivered operational 3-pillar cashflow tracking for Agents (**Digital Float**, **Physical Hand Cash**, and **Payable Due to Admin**) with step-by-step settlement capabilities.
* Executed full QA verification and baseline system reset (`Admin Treasury = ৳ 100,000.00`).
