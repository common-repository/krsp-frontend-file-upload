/**
 * The main Vue instance for our plugin settings page
 * @link https://vuejs.org/v2/guide/instance.html
 */
new Vue( {

	// DOM selector for our app's main wrapper element
	el: '#settings_page',

	// Data that will be proxied by Vue.js to provide reactivity to our template
	data: {

		isSaving: false,
		message: '',

		vm: KRSP_Data.options,
	},
	mounted: function(){
		console.debug("Uploader settings: ", this.vm);
		if(!this.vm.uploadLimit > 0){
			this.vm.uploadLimit = 1
		}
	},
	// Methods that can be invoked from within our template
	methods: {
		// Remove Notice
		dismissMessage(){
			this.message = ''
		},
		// Save the options to the database
		saveOptions: function() {

			// set the state so that another save cannot happen while processing
			this.isSaving = true;

			// Make a POST request to the REST API route that we registered in our PHP file
			jQuery.ajax( {

				url: KRSP_Data.siteUrl + '/wp-json/krsp/v1/save',
				method: 'POST',
				data: this.vm,

				// set the nonce in the request header
				beforeSend: function( request ) {
					request.setRequestHeader( 'X-WP-Nonce', KRSP_Data.nonce );
				},

				// callback to run upon successful completion of our request
				success: () => {

					this.message = 'Settings saved';
					// setTimeout( () => this.message = '', 3500 );
				},

				// callback to run if our request caused an error
				error: ( data ) => console.error(data.responseText),

				// when our request is complete (successful or not), reset the state to indicate we are no longer saving
				complete: () => this.isSaving = false,
			});
		}, // end: saveOptions
	}, // end: methods
}); // end: Vue()
