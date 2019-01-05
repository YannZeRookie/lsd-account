function gotoURL(url)
{
	window.location = url;
}

function displayAdd(user,site)
{ /* Convenient function to hide an e-mail address from robots */
	var str;
	
	str = '<a class="GenLink" href=\"';
	str += 'mail';
	str += 'to:' + user +'@';
	str += site + '\">';
	str += user;
	str += '@';
	str += site;
	str += '</a>';
	
	document.write(str);
}

function displayAddWithBody(user,site,body)
{ /* Same as displayAdd but inserts 'body' as the tag body */
	var str;
	
	str = '<a class="GenLink" href=\"';
	str += 'mail';
	str += 'to:' + user +'@';
	str += site + '\">';
	str += body;
	str += '</a>';
	
	document.write(str);
}
