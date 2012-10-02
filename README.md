\mainpage

A simple content management system pretending to be a chat script.

No database is required as everyting is done with SimpleTextStorage. However,
because of this, and the massive amount of hooks used, this is only suitable
for low traffic sites.

\defgroup WebApi

\brief Javascript clients use the WebApi to communicate with the Server.

The server and client are entirely seperated. The server should never output
html code. Rendering html is left entirely to the javascript (or other) client.

Most classes will provide their service to the javascript client through an
Api file.

@defgroup Route

@brief A class, such as User, will register on the Server to process specific
urls. These are called routes.

A javascript client communicates with the server by accessing a route (a url
path). Each route performs a limited and specific task. The client can pass
options and parameters for the route in a posted variable named 'payload'. The
payload is a json encoded string that normally contains a nested array, or
object.

Each route handler, basicly a function , is responsible for documenting how it's
payload should be structured. A route handler often will return a json encoded
string containing the results of the request. The structure of this response
should also be documented in the function docblock.
