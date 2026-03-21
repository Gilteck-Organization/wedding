# Wedding site — user flows (non-technical)

This document describes how different people use the wedding website and admin tools. No technical knowledge required.

---

## 1. Someone invited to the wedding (RSVP)

**As a guest,** I want to tell the couple whether I’m coming and how many people are in my party **so that** they can plan seating and food.

**What I do**

1. Open the wedding website (link or address the couple shared).
2. Scroll to the **RSVP** section on the home page.
3. Fill in my **name** and **WhatsApp number**.
4. Choose **Yes** or **No** for attendance.
5. If **Yes**, choose **Just me** or **Me and one guest** (party size).
6. Tap **Submit RSVP**.

**What I see after**

- A short thank-you message confirming my RSVP was received.

**If the guest list is full**

- If every seat is already taken, I **won’t** see the RSVP form.
- Instead I see a kind message explaining that the celebration is at capacity.

---

## 2. Viewing my digital access card (after I’m approved)

**As an approved guest,** I want to open my **access card** online **so that** I can see my invitation-style card and the QR code the couple may ask for at the door.

**What I do**

1. Use the **access card link** the couple sends you (or that appears after they approve you—however they choose to share it).
2. The page opens **without** asking for a password or special name.

**What I see**

- The wedding access card design, my name (and party note if it applies), and a **QR code** on the card.

**Important:** This page is meant to be **easy to open and show**. It is **not** the same as the “verify at the door” step below.

---

## 3. Scanning the QR code on someone’s access card (check-in helper)

**As a friend, family member, or door volunteer,** I want to **scan the QR on a guest’s phone** and prove I’m allowed to help check them in **so that** the couple’s team sees a clear “verified” screen for **that** guest.

**What I do**

1. Open the **phone camera** (or a QR app) and **scan the QR** shown on the guest’s access card.
2. The phone opens a web page.
3. If I’m **not** logged in as admin:
   - I see a simple screen asking for the **access name** (a shared word or phrase the couple gave to trusted people—not the guest’s name on the invite).
   - I type that access name and continue.
4. If the name **matches** what the couple set up in the admin area → I see a **verified / attendance OK** screen for **the guest whose QR I scanned**.
5. If the name **doesn’t** match → I’m taken back to the wedding home page **without** an error message (on purpose, for privacy).

**If I’m logged in as an admin** on the same phone/browser

- After scanning, I may go **straight** to the verified screen **without** typing the access name (for staff use).

---

## 4. Wedding admin — day-to-day

**As the couple or their coordinator,** I want a **private admin area** **so that** I can manage RSVPs, access cards, and who can pass the QR check-in.

### 4.1 Signing in and out

- I open the **login** page (address the couple uses for admin only).
- I sign in with **email and password**.
- When finished, I use **Log out** in the top bar.

### 4.2 Dashboard

**As an admin,** I want a **summary** **so that** I see how many people RSVP’d, how many seats are used, and what’s still pending.

- I click **Dashboard** in the top menu.
- I read the numbers (capacity, seats used, remaining spots, pending approvals, etc.).

### 4.3 RSVPs list

**As an admin,** I want to **see every RSVP** and **approve** guests **so that** they get an access card and QR.

- I click **RSVPs**.
- For each row I can use the **⋮** menu:
  - **Approve** — creates/links the guest and turns on their access card + QR (when attendance allows).
  - **Access card** — opens the **public** card page in a new tab (what the guest sees).
  - **Revoke attendance** — marks them not attending and turns off the access card until you approve again (if you use that workflow).

I can also **Export CSV** to download the list for a spreadsheet.

### 4.4 Access names (shared check-in phrase)

**As an admin,** I want to set **one or more shared access names** **so that** people scanning **any** guest’s QR can complete verification **without** using the guest’s invitation name.

- I click **Access names** in the top menu.
- I **add** phrases or codes (examples: a family motto, an event code—whatever you agree to share with door helpers).
- I can **remove** a name if we stop using it.

**Notes for the couple**

- These names work for **every** access-card QR, not one name per guest.
- If **no** access names are saved, guests scanning the QR **cannot** complete the name step—but **logged-in admins** still see the verified screen when they scan.

### 4.5 My profile

**As an admin,** I want to **change my name, email, or password** **so that** my account stays correct and secure.

- I click my **name** (or **Profile**) in the top bar.
- I update details and save; I use the password section only when I want to change the password.

---

## 5. Quick “who does what” summary

| Person | Main actions |
|--------|----------------|
| **Guest** | RSVP on home page; open access card link when the couple shares it. |
| **Door helper** | Scan QR on guest’s phone; enter the **access name** the couple gave you; show verified screen if correct. |
| **Admin** | Log in → Dashboard & RSVPs → Approve / Access card / Revoke → set **Access names** → Profile as needed. |

---

## 6. Privacy & behaviour (plain language)

- Wrong access name after a QR scan → **silent** return to the home page (no “wrong password” message).
- The **access card link** is for **showing** the pretty card and QR.
- The **QR itself** leads to the **verify** flow that may ask for the **access name** (unless you’re already signed in as admin).

If anything here doesn’t match what you see on screen, the couple’s technical contact can align the site with this intended flow.
