SUMMARY
====================
Mailtron 5000 provides a simple suite of functions to assist developers in implementing system-generated emails.
It supports HTML, plaintext, and multipart MIME (HTML + plaintext) email formats via the mimemail module.


INSTALLATION
====================
Standard module installation, see http://drupal.org/node/70151 for detailed instructions.


CONFIGURATION
====================
None: Mailtron 5000 has no configurable settings at this time. A UI module is in the planning stages.


USAGE
====================
Add mailtron as a dependency in your module and then invoke the functions you need. Typically a bare bones integration
will require the following:

mailtron_email_form()
mailtron_mail_load()
mailtron_send_mail()

For a detailed example of how to use this module, please refer to mailtron_example.module in the examples folder.


CREDIT
====================
All credit goes to the maintainers of Mimemail (http://drupal.org/projects/mimemail).


FAQ
====================
Q: what's up with the derpy name?
A: It's a running joke at work but I won't bore you with the details. Mostly I just have a juvenile sense of humor.

Q: Why did you write this thing?
A: After the fourth time a module spec containing a multipart MIME email feature landed in my lap at work I decided something needed to be done
   to reduce the number of duplicate implementations.

Q: Any plans on making this thing useful for non-developers?
A: Maybe. I've got this half-assed idea for a UI module that integrates with actions & rules. It would probably also define a reusable email template
   node type. Unfortunately with a full time job, an infant, and diverse (frequently neglected) hobbies the odds of finding the time are pretty slim.
   For now it's sufficient for me to scratch my own itch.

