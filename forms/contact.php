<form action="/inc/formHandler.php" method="POST">

    <input type="hidden" name="subject" value="SonaCMS Contact Form Submission">
    <input type="hidden" name="redirect" value="/thank-you">

    <!-- Honeypot: hidden from humans, bots tend to fill it.
         formHandler.php should silently discard the submission if this
         field is non-empty. Kept off-screen rather than display:none,
         which some bots specifically skip. -->
    <div style="position:absolute; left:-5000px;" aria-hidden="true">
        <input type="text" name="website" tabindex="-1" autocomplete="off">
    </div>

    <!-- Full Name -->
    <div>
        <label for="fullName">Full Name:</label>
        <input type="text" id="fullName" name="fullName" placeholder="John Doe" required>
    </div>

    <!-- Email -->
    <div>
        <label for="userEmail">Email Address:</label>
        <input type="email" id="userEmail" name="userEmail" placeholder="name@example.com" required>
    </div>

    <!-- Country -->
    <div>
        <label for="country">Country:</label>
        <input type="text" id="country" name="country" placeholder="Country" required>
    </div>

    <!-- Opt-in -->
    <div>
        <input type="checkbox" id="subscribe" name="subscribe" value="yes">
        <label for="subscribe">Subscribe to our Mailing List</label>
    </div>

    <!-- Message -->
    <div>
        <label for="message">Message:</label>
        <textarea id="message" name="message" rows="4" cols="30" placeholder="Type your message here..."></textarea>
    </div>

    <!-- Submit -->
    <div>
        <button type="submit">Submit Form</button>
    </div>

</form>