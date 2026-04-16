# ERP26 Agent Reference

**Stack:** Laravel 12, AdminLTE, multi-tenant, SMS, double-entry accounting.

## Entities
- **Customer:** `opening_balance`, `outstanding_balance`, ledger account
- **Invoice:** `paid_amount`, `due_amount`, `payment_status`, `previous_balance`(snapshot), `initial_paid_amount`(snapshot)
- **Transaction:** `type` (credit|debit), `amount`, `discount_amount`, `invoice_id?`, `return_id?`, `account_id`
- **Accounting:** `Account` > `AccountGroup` > `Voucher` > `VoucherEntry` (double-entry, does NOT affect customer balance)

## Customer Balance — Source of Truth: `transactions`
- Formula: `opening_balance + Σcredits - Σdebits`
- Debit value = `amount + discount_amount`
- Credits = invoice charges, refunds paid out; Debits = payments, discounts, returns
- On create: `outstanding_balance = opening_balance ?? 0`
- `customer_balance_summary` updated by `PaymentAllocationService`; `total_payments_amount` includes discounts

## Invoice Allocation (`PaymentAllocationService`)
1. Reset all invoice payment fields in memory
2. Apply invoice-specific debits first, then FIFO across oldest invoices
3. `paid_amount` includes discounts; `due_amount = total - paid_amount`
4. Status: `paid | partial | due`
- **MUST call after every invoice/transaction/return change**
- `previous_balance` and `initial_paid_amount` are immutable snapshots — use for display only, never recompute

## Returns & Refunds
- Return → debit transaction (reduces balance); optionally links `invoice_id`
- Refund (when return > outstanding) → credit transaction; capped at `return_total - outstanding_before`; requires cash/bank account → auto-posts Payment voucher

## Auto-Posting (`TransactionObserver` → `AutoPostingService`)
| Event | Voucher | Dr | Cr |
|---|---|---|---|
| Invoice | Sales | Customer | Sales |
| Payment (debit) | Receipt | Cash/Bank | Customer |
| Return refund (credit) | Payment | Customer | Cash/Bank |

- `GeneralLedgerService` updates account balances only — never customer balance
- Voucher re-posts on: amount, discount, method, or account change

## Key Files
- `app/Services/PaymentAllocationService.php`
- `app/Services/Accounting/AutoPostingService.php`
- `app/Services/Accounting/GeneralLedgerService.php`
- `app/Observers/TransactionObserver.php`
- `app/Http/Controllers/{InvoiceController,TransactionController,ProductReturnController}.php`

## Guardrails
- Never update `customers.outstanding_balance` directly — always reallocate
- Never use vouchers to sync customer balances
- Never hand-edit `paid_amount` or `due_amount` — derived from allocation only
