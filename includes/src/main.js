import Vue from 'vue';
import axios from 'axios'
import VueAxios from 'vue-axios';
import i18n from './lang/lang'
// import vuexI18n from 'vuex-i18n';
import Vuex from 'vuex'
import VueSweetAlert from 'vue-sweetalert'
import store from './store'
import App from './App.vue'
import VueProgressiveImage from 'vue-progressive-image'

Vue.use(VueAxios, axios)
Vue.use(Vuex, store)
Vue.use(VueSweetAlert)
Vue.use(VueProgressiveImage)
Vue.axios.defaults.headers.common['X-WP-Nonce'] = window.krsp_file_upload.ajax_nonce;
module.exports = Vue;
Vue.filter('kb', function (value) {
	if (value > 10000000) {
		return (value / 1000000).toFixed(0) + ' Mb';
	}
	return (value / 1024).toFixed(0) + ' Kb';
});

const file_upload = document.getElementById("krsp_file_upload")
if(file_upload){
	new Vue({
	i18n,
	store,
	el: '#krsp_file_upload',
	components: { App }
})
} else {
	console.debug("Element not present on page, skipping");
}
