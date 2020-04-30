# wp_vuln_scanner
EC521 Cybersecurity Project

The goal of this project is to detect vulnerabilities in WordPress plugins. Our Solution utilizes the Wordpress Search API and nikic/PHP-Parser for AST tree parsing and traversal. 


We focused on SQL Injection and Cross-site Scripting (XSS) vulnerabilities as we found these are one of the most common types of vulnerabilities and are easy to classify. 


Ideally this tool would be used as a discovery platform for finding POTENTIAL vulnerabilities


Code Entry Points:
	All Plugins (including download): python3 WP_API/plugin_api.py
	Single Plugin (zip file from wp): ./WP_API/unpack_and_parse /dir
	
