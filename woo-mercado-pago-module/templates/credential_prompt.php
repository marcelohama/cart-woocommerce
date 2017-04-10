<script type="text/javascript">

	( function() {
		var WooMP = {}
		/*WooMP.checkCredentials = function () {
			WooMP.AJAX({
				url: url,
				method : "GET",
				timeout : 5000,
				error: function() {
					// Request failed.
				},
				success : function ( status, response ) {
					if ( response.status == 200 ) {
						
					} else if ( response.status == 400 || response.status == 404 ) {
						
					}
				}
			});
		}*/
		WooMP.referer = (function () {
			var referer = window.location.protocol + "//" +
				window.location.hostname + ( window.location.port ? ":" + window.location.port: "" );
			return referer;
		})();
		WooMP.AJAX = function( options ) {
			var useXDomain = !!window.XDomainRequest;
			var req = useXDomain ? new XDomainRequest() : new XMLHttpRequest()
			var data;
			options.url += ( options.url.indexOf( "?" ) >= 0 ? "&" : "?" ) + "referer=" + escape( WooMP.referer );
			options.requestedMethod = options.method;
			if ( useXDomain && options.method == "PUT" ) {
				options.method = "POST";
				options.url += "&_method=PUT";
			}
			req.open( options.method, options.url, true );
			req.timeout = options.timeout || 1000;
			if ( window.XDomainRequest ) {
				req.onload = function() {
					data = JSON.parse( req.responseText );
					if ( typeof options.success === "function" ) {
						options.success( options.requestedMethod === "POST" ? 201 : 200, data );
					}
				};
				req.onerror = req.ontimeout = function() {
					if ( typeof options.error === "function" ) {
						options.error( 400, {
							user_agent:window.navigator.userAgent, error : "bad_request", cause:[]
						});
					}
				};
				req.onprogress = function() {};
			} else {
				req.setRequestHeader( "Accept", "application/json" );
				if ( options.contentType ) {
					req.setRequestHeader( "Content-Type", options.contentType );
				} else {
					req.setRequestHeader( "Content-Type", "application/json" );
				}
				req.onreadystatechange = function() {
					if ( this.readyState === 4 ) {
						if ( this.status >= 200 && this.status < 400 ) {
							// Success!
							data = JSON.parse( this.responseText );
							if ( typeof options.success === "function" ) {
								options.success( this.status, data );
							}
						} else if ( this.status >= 400 ) {
							data = JSON.parse( this.responseText );
							if ( typeof options.error === "function" ) {
								options.error( this.status, data );
							}
						} else if ( typeof options.error === "function" ) {
							options.error( 503, {} );
						}
					}
				};
			}
			if ( options.method === "GET" || options.data == null || options.data == undefined ) {
				req.send();
			} else {
				req.send( JSON.stringify( options.data ) );
			}
		}
		this.WooMP = WooMP;
	} ).call();

</script>

<div id="dialog-form" title="Create new user" style="margin-bottom:-10px; margin-top:8px;">
	<form>
		<fieldset>
			<input type="password" placeholder="Client ID" name="client_id" id="client_id" class="text ui-widget-content ui-corner-all">
			<input type="password" placeholder="Client Secret" name="client_secret" id="client_secret" class="text ui-widget-content ui-corner-all">
			<input type="button" value="<?php echo $access_token; ?>" name="client_secret" id="submit" class="button" style="margin:1px; height:25px;">
		</fieldset>
	</form>
</div>
