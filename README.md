# IRIS Payments (EveryPay) - PrestaShop Module

Ένα module πληρωμών για το PrestaShop που ενσωματώνει το IRIS / EveryPay payment gateway.

## 📋 Περιγραφή

Το **IRIS Payments** module επιτρέπει στο PrestaShop κατάστημά σας να δέχεται πληρωμές μέσω του IRIS / EveryPay payment gateway. Υποστηρίζει πλήρη ενσωμάτωση με το API του EveryPay, παρέχοντας ασφαλή και αξιόπιστη επεξεργασία πληρωμών.

## ✨ Χαρακτηριστικά

- ✅ Πλήρης ενσωμάτωση με IRIS / EveryPay API
- ✅ Υποστήριξη Sandbox mode για δοκιμές
- ✅ Ασφαλής επεξεργασία πληρωμών με hash verification
- ✅ Callback URL για επιβεβαίωση πληρωμών
- ✅ Προσαρμοσμένες καταστάσεις παραγγελιών
- ✅ Πολύγλωσση υποστήριξη (Ελληνικά/Αγγλικά)
- ✅ Συμβατό με PrestaShop 1.7+
- ✅ Bootstrap UI για εύκολη διαμόρφωση
- ✅ Logo και branding customization

## 📦 Εγκατάσταση

### Μέθοδος 1: Μέσω Back Office
1. Κατεβάστε το module
2. Συνδεθείτε στο PrestaShop Back Office
3. Πηγαίνετε στο **Modules > Module Manager**
4. Κάντε κλικ στο **Upload a module**
5. Επιλέξτε το ZIP αρχείο του module
6. Κάντε κλικ στο **Install**

### Μέθοδος 2: Χειροκίνητα
1. Αποσυμπιέστε το module στον φάκελο `/modules/irispayments/`
2. Πηγαίνετε στο **Modules > Module Manager**
3. Αναζητήστε το "IRIS Payments"
4. Κάντε κλικ στο **Install**

## ⚙️ Διαμόρφωση

Μετά την εγκατάσταση, διαμορφώστε το module:

1. Πηγαίνετε στο **Modules > Module Manager**
2. Αναζητήστε το "IRIS Payments" και κάντε κλικ στο **Configure**
3. Συμπληρώστε τα παρακάτω πεδία:

### Ρυθμίσεις

| Πεδίο | Περιγραφή |
|-------|-----------|
| **Public Key** | Το Public Key από τον λογαριασμό σας στο EveryPay |
| **Secret Key** | Το Secret Key από τον λογαριασμό σας στο EveryPay |
| **Merchant Name** | Το όνομα του καταστήματός σας (εμφανίζεται στη σελίδα πληρωμής) |
| **Order State** | Η κατάσταση παραγγελίας μετά από επιτυχή πληρωμή |
| **Sandbox Mode** | Ενεργοποιήστε για δοκιμαστικές συναλλαγές (προεπιλογή: Enabled) |

### Λήψη API Keys

1. Συνδεθείτε στο [EveryPay Dashboard](https://dashboard.everypay.gr)
2. Πηγαίνετε στις ρυθμίσεις του λογαριασμού σας
3. Αντιγράψτε το **Public Key** και **Secret Key**
4. Για δοκιμές, χρησιμοποιήστε τα Sandbox keys

## 🔧 Τεχνικές Λεπτομέρειες

### Δομή Αρχείων

```
irispayments/
├── irispayments.php          # Κύριο αρχείο module
├── index.php                 # Security file
├── README.md                 # Αυτό το αρχείο
├── assets/
│   └── logo.svg             # Module logo
├── controllers/
│   └── front/
│       ├── callback.php     # Callback handler για επιβεβαίωση πληρωμών
│       └── session.php      # Session creation & redirect στο EveryPay
├── translations/
│   └── el.php              # Ελληνικές μεταφράσεις
└── views/
    └── templates/
        └── hook/
            ├── payment_info.tpl     # Πληροφορίες πληρωμής στο checkout
            └── payment_success.tpl  # Μήνυμα επιτυχίας
```

### Hooks

Το module χρησιμοποιεί τα εξής hooks:

- `paymentOptions` - Εμφανίζει την επιλογή πληρωμής IRIS στο checkout
- `displayOrderConfirmation` - Εμφανίζει μήνυμα επιβεβαίωσης παραγγελίας
- `displayPaymentTop` - Εμφανίζει μηνύματα σφάλματος στη σελίδα πληρωμής

### API Endpoints

- **Sandbox**: `https://sandbox-api.everypay.gr`
- **Production**: `https://api.everypay.gr`

### Controllers

#### SessionModuleFrontController
- Δημιουργεί νέο payment session
- Υπολογίζει το ποσό σε cents
- Δημιουργεί UUID για τη συναλλαγή
- Redirect στο EveryPay checkout

#### CallbackModuleFrontController
- Λαμβάνει το callback από το EveryPay
- Επαληθεύει το hash για ασφάλεια
- Ελέγχει την κατάσταση της πληρωμής
- Δημιουργεί την παραγγελία στο PrestaShop
- Redirect στη σελίδα επιβεβαίωσης

## 🔐 Ασφάλεια

Το module εφαρμόζει τα εξής μέτρα ασφαλείας:

- ✅ SSL/HTTPS required για όλες τις συναλλαγές
- ✅ Hash verification για callbacks
- ✅ Base64 decoding και JSON validation
- ✅ Έλεγχος cart validity
- ✅ Secure key storage στη βάση δεδομένων
- ✅ Input validation και sanitization

## 🌍 Γλώσσες

Το module υποστηρίζει:
- Ελληνικά (el)
- Αγγλικά (en)

Για προσθήκη νέων γλωσσών, δημιουργήστε αντίστοιχο αρχείο στον φάκελο `translations/`.

## 📝 Συμβατότητα

- **PrestaShop**: 1.7.0.0 και νεότερη
- **PHP**: 7.1+
- **Currencies**: Όλα τα νομίσματα που υποστηρίζει το EveryPay

## 🐛 Troubleshooting

### Το module δεν εμφανίζεται στο checkout

- Ελέγξτε ότι το module είναι ενεργοποιημένο
- Επαληθεύστε ότι έχετε συμπληρώσει τα API keys
- Βεβαιωθείτε ότι το νόμισμα του cart υποστηρίζεται

### Σφάλμα κατά την πληρωμή

- Ελέγξτε τα logs στο PrestaShop
- Επαληθεύστε τα API keys (Public & Secret)
- Βεβαιωθείτε ότι το Sandbox mode είναι σωστά ρυθμισμένο
- Ελέγξτε το callback URL στις ρυθμίσεις του EveryPay

### Callback URL

Το callback URL πρέπει να είναι προσβάσιμο:
```
https://yourdomain.com/module/irispayments/callback
```

Βεβαιωθείτε ότι:
- Είναι προσβάσιμο από το internet
- Χρησιμοποιεί HTTPS
- Δεν μπλοκάρεται από firewall ή .htaccess rules

## 📄 Άδεια Χρήσης

Αυτό το module αναπτύχθηκε από **ALPHA DEV**.

## 📊 Changelog

### Version 1.0.0
- Αρχική έκδοση
- Πλήρης ενσωμάτωση με IRIS / EveryPay API
- Υποστήριξη Sandbox mode
- Πολύγλωσση υποστήριξη (EL/EN)

---
**Σημείωση**: Βεβαιωθείτε ότι έχετε ενεργό λογαριασμό στο EveryPay και έχετε λάβει τα απαραίτητα API credentials πριν από τη χρήση του module σε παραγωγικό περιβάλλον.