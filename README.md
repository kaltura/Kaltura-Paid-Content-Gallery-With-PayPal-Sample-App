Kaltura PayPal Gallery
==================
Kaltura Sample Application showing how to setup your Kaltura account and use PayPal for creating a gallery of pay-to-watch videos.
This sample shows you how to sell single videos and also setup subscriptions for channels (categories). Once you setup your account using
the provided setup wizard, your account will be ready to start selling videos to customers.

How to get started
------------
This application comes with everything you need to get running. All that you need to do is download this package and host it on your server. 
The application itself uses Kaltura so you must create an account. You may start a free trial at http://corp.kaltura.com and you must obviously
upload your own content for the gallery to display any videos. You may find your uploaded entries at http://www.kaltura.com/index.php/kmc/kmc4#content|manage
Upon the first load of the home page, nothing will load because the account has not been set up yet. 
Click the Admin Account Wizard button and you should be brought to the PayPal Account Wizard. Log into the tool with your Kaltura credentials
and you will be brought to a menu to get your account ready for use with the gallery.
The tool has only 4 steps to follow and should be fairly easy to follow. The last step which involves generating the kalturaConfig.php file is what
will finally allow your video gallery to load but this should only be performed after all the other steps have been completed.
The generated file will automatically be saved in the correct directory on your server but it will also become available as a download in case
you want your own copy of the file. You do not have to manually insert it though.

How it works
------------
* The first step in the account wizard performs two simple actions for your account. It will ask you to name an Access Control Profile and optionally
give that profile a preview time. This Access Control Profile is what will allow your videos to only be viewed in their entirety if they are
purchased by your customer. When they load a video, they will either be given a free preview first or immediately be informed that in order to
watch the content they must pay for it. In addition to this, behind the scenes the wizard will also create two metadata profiles. These two profiles,
one for individual videos and the other for your channels (categories), will store the pricing information for your content. This includes their
price, the sales tax and the currency that you would like to use.
* The next step involves creating a player that will allow your customers to purchase the videos. You must already have a player created in your
Kaltura KMC account under the Studio tab (http://www.kaltura.com/index.php/kmc/kmc4#studio|playersList). This step will either let you clone an
existing player or simply overwrite the old one. What it does is create an extra button either on screen or in the player's control panel which,
when clicked, will display your video's pricing information to the customer. This new player will also be able to automaticlaly show the pricing
information as soon as the video's preview has finished. For more advanced users, you may even provide your own javascript handler to perform a
different purchase routine when these buttons are clicked.
* The third step is where you will actually be able to set the prices for your videos and your channels. Either select the 'Individual Entires' tab
or the 'Categories' tab and you will be shown either all the videos or categories in your account. Clicking on a thumbnail for a video will then
bring up a new window where the pricing information may be set. You will notice that there is a checkbox labeled 'Paid Content' that when checked,
will show you all the different fields you must fill out for your video to properly work with PayPal's Digital Goods checkout. Enter the price, sales
tax and the currency you would like to use. Finally, at this point you should have created an Access Control Profile that you would like to use and
you should choose the appropriate one. You may create multiple Access Control Profiles under the 'Setup Account' menu option and thus now have the
option to choose the one you want to use for that specific video or channel (You might some videos to have a 10 second preview while others a 5 minute
preview). After submitting the information the content will be ready to sell. You may also keep the 'Paid Content' option unchecked and submitting
will actually make that content free again in case you ever want to stop selling it (Doing this will simply set the metadata profile's 'Paid' option
to false so that the gallery knows to use your free content player. In addition to this, it will set the video's access control profile to whatever
profile you have deemed as the Default in your KMC). When you're done giving prices to your content, click done and you will return to the main menu.
* Finally, now that your account has been fully setup to work with PayPal's libraries, you may generate a config file. As mentioned above, this will
automatically place the file in the correct directory and will contain all the information required to run your video gallery.
* In this particular sample, when the user buys a video using their PayPal account, the video is purchased for their machine name and their
IP address. Therefore as long as they use the same machine on that IP address, they will continue to have access to the content.

Pricing Architecture
--------------------
You may have noticed that you can put prices on both videos and channels (categories). You might ask yourself, if a video is in a channel that costs
$30 to subscribe to, but the individual video also has its own price of $2, which price would the customer pay to access the video? As long as a video
has an individual price profile, the customer can buy that video regardless of a subscription to the channel. However, if no pricing is set for the
video itself, the customer can only buy the video as part of a subscription. To offer the video as a standalone purchase, you must set the individual
pricing for it.

How purchases are stored
------------------------
PayPal's original HTML5 library uses local storage to keep a record of all the digital goods that the customer has purchased. Unfortunately, if the customer clears their browser data (including their HTML5 local storage) then any record of the purchase is erased. We have bypassed this verification system and instead used user custom metadata. When a customer makes a purchase, their user ID and purchases are stored with Kaltura so the information cannot be lost.

Files
-----

* index.php - The front page that the user interacts with
* client/pptransact.js - Paypal's Script for digital goods express checkouts
* client/style.css - The styling for the front page
* server/kalturaConfig.php - Stores all the constants such as the authorization information and player IDs
	(This file can be automatically generated using the Account Wizard)
* server/reloadEntries.php - Displays the current page of entries
* server/reloadCategories.php - Displays a list of available channels on the account

Folders
-------

* AccountWizard - Contains a setup wizard that allows the admin to completely set up an account to use the paid content gallery
* server - Contains the html5-dg library with other files listed above
	(https://github.com/paypalx/html5-dg)
* server/client - Contains the Kaltura PHP5 client library
	(http://www.kaltura.com/api_v3/testme/client-libs.php)
* server/cert - Contains the certification information to securely connect to PayPal

Outstanding Issues
------------------
* At the moment there is no way to expire a purchase based on time. Once a user purchases some content, they will own it forever. There is also no way
to refund the customer. Although the refund can be performed on PayPal, there is nothing in PayPal's library that allows us to then revoke the
purchase and eliminate access to the videos (https://github.com/paypalx/html5-dg/issues/9)
* There is also no way to purchase a subscription in the sense of recurring payments. In its current state, a customer may purchase access to a
channel (category) so that every time a new video is added to said category they will be able to watch it without having to pay for it again.
This however means they only have to pay for the channel once and there is no way to charge them on a monthly/annual basis (https://github.com/paypalx/html5-dg/issues/10)