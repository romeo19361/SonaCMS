<form action="/inc/formHandler.php" method="POST">
    <input type="hidden" name="subject" value="SonaCMS Contact Form Submission">
    <input type="hidden" name="redirect" value="/thank-you">
    <!-- Honeypot: real users never see or tick this. Bots that blindly fill
         forms will. A CHECKBOX is used (not a text field) because browser
         autofill/password managers fill hidden TEXT inputs with stray values
         like a town or email — but they do not tick hidden checkboxes. This
         avoids genuine submissions being wrongly dropped as spam.
         formHandler.php discards the submission if this box is ticked. -->
    <div style="position:absolute; left:-5000px;" aria-hidden="true">
        <input type="checkbox" name="contact_time" tabindex="-1" autocomplete="off">
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