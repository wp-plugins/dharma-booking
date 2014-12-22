PAYSTATION 3 Party Paystation Hosted with POST Back Sample Code
============================

For PHP5.3+

This Paystation sample was released 06 June 2013.

Please report any bugs to info@paystation.co.nz.

While all care has been taken while developing this sample code, Paystation Limited will not accept any liability for any loss(es) incurred during the testing of this code.

Files within package:

index.php  - This page is a simple HTML form which passes the amount to paystation_refresh.php
paystation_refresh.php - This page performs the initiation POST request to Paystation, reads the XML packet that is sent back and then re-directs the user to the Paystation hosted screens.
post_return.php - This page is the POST back sample file.  This should show you the basics of POST back with Paystation.
paystation_return.php - This page is where the end user is re-directed to after payment.

Installation Instructions

1. Unzip the files and place on your webserver(You will need to have PHP installed)
2. Open paystation_refresh.php
3. Add your Paystation ID to the $paystationId variable.
4. Add your Gateway ID to the $gatewayId variable.
5. Email info@paystation.co.nz with your return URL and POST back URL.
6. Once the return and POST back URL is set by a member of the Paystation team go to the index.php page and enter the dollar amount you want to test.
7. Test cards can be found here: http://paystation.co.nz/test-card-numbers

Note => There are three basic functions on the post_return.php page:
- CreateXMLFile creates a XML file called test.xml with the data. The folder you are creating the file in will need to its chmod set to 777.
- sendEmail sends an email with the PHP variables created from the XML packet.  You will need to add your email into the function.
- sendXMLFile sends an email with the array of everything in the POST packet.

The functions in the post_return.php are for demo purposes only you should really store this data in a database.  By default all functions are commented out.  To call the function, uncomment the function you want to use(remove the // at the beginning of the line).

The sample code is an example of what you can do and we have tried to make it as easy as possible to read and understand.  If you have any suggestions please feel free to send them to info@paystation.co.nz.

If you get stuck with this sample code please send any questions to info@paystation.co.nz, please note it is always good when asking for support to add as much detail to your email as possible :)
