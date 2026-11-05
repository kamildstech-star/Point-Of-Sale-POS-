# Local audit evidence

Captured 2026-10-19. Driver: mysql. Server: 10.4.32-MariaDB.

| Table | Observed rows |
| --- | --- |
| business_dealers | 0 |
| cache | 0 |
| cache_locks | 0 |
| daily_account | 0 |
| expenses | 0 |
| failed_jobs | 0 |
| job_batches | 0 |
| jobs | 0 |
| ledger | 0 |
| ledgers | 0 |
| logs | 0 |
| migrations | 45 |
| order_items | 0 |
| order_random_items | 0 |
| orders | 0 |
| password_reset_tokens | 0 |
| permission | 20 |
| products | 0 |
| return_items | 0 |
| returns | 0 |
| roles | 2 |
| schools | 0 |
| sessions | 2 |
| settings | 1 |
| sizes | 0 |
| stocks | 0 |
| todolist | 0 |
| users | 2 |

All 45 legacy migrations are recorded as applied. Local inventory, sales, returns, dealers, ledgers, expenses and daily accounts are empty. No duplicate stock combinations/codes, negative quantities or checked orphan relationships were found. These checks on an empty local business database do not establish production data integrity.

Private JSON reports retain exact stock-by-ID balances, business-row fingerprints, aggregate financial baselines, indexes and foreign keys. Customer identities, password hashes and connection secrets are not reproduced here.

## Existing route issues

| Route | Action | Issue |
| --- | --- | --- |
| businessDealer | App\Http\Controllers\BusinessDealerController@showBusinessDealer | missing_controller |
| businessDealers | App\Http\Controllers\BusinessDealerController@businessDealerindex | missing_controller |
| businessDealer/edit/{id} | App\Http\Controllers\BusinessDealerController@editbusinessDealer | missing_controller |
| businessDealer/{id} | App\Http\Controllers\BusinessDealerController@updatebusinessdealer | missing_controller |
| businessDealers/create | App\Http\Controllers\BusinessDealerController@createBusinessDealer | missing_controller |
| businessDealer/{id} | App\Http\Controllers\BusinessDealerController@deleteBusinessDealer | missing_controller |
| schoolmargin | App\Http\Controllers\OrderController@schoolmargin | missing_method |
| check/stock | App\Http\Controllers\OrderController@checkstock | missing_method |
| order/edit/{id} | App\Http\Controllers\OrderController@editorder | missing_method |
| order/{id} | App\Http\Controllers\OrderController@updateorder | missing_method |
| fetchprice | App\Http\Controllers\returnOrderController@fetchPrice | missing_method |
| login | App\Http\Controllers\Auth\LoginController@login | missing_controller |
| logout | App\Http\Controllers\Auth\LoginController@logout | missing_controller |
| register | App\Http\Controllers\Auth\RegisterController@showRegistrationForm | missing_controller |
| register | App\Http\Controllers\Auth\RegisterController@register | missing_controller |
| password/reset | App\Http\Controllers\Auth\ForgotPasswordController@showLinkRequestForm | missing_controller |
| password/email | App\Http\Controllers\Auth\ForgotPasswordController@sendResetLinkEmail | missing_controller |
| password/reset/{token} | App\Http\Controllers\Auth\ResetPasswordController@showResetForm | missing_controller |
| password/reset | App\Http\Controllers\Auth\ResetPasswordController@reset | missing_controller |
| emailsend | App\Http\Controllers\EmailController@sendEmail | missing_method |

A full `route:list` still fails on the missing BusinessDealerController. The audit reads route registrations without invoking their business actions. Baseline scaffold tests initially produced 1 pass/1 failure; the guest redirect expectation was corrected, and the new API suite uses an isolated test database. See the implementation and recovery documents for validation evidence and remaining deployment gates.
