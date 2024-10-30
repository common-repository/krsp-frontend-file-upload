import Vue from 'vue';
var Vuex = require('vuex');

Vue.use(Vuex)

module.exports = new Vuex.Store({
    state: {
        queue: [],
        uploads: [],
        isLoading: false,
        isSaving: false,
        isDeleting: false,
        message: {}
    },
    mutations: {
        RESET_UPLOADER(state){
            // state.uploads = [],
            state.isLoading = false
            state.queue = []
        },
        IS_LOADING(state, status){
            state.isLoading = status
        },
        IS_SAVING(state, status){
            state.isSaving = status
        },
        IS_DELETING(state, status){
            state.isDeleting = status
        },
        UPDATE_IMAGE(state, image){

            let theIndex = findIndexByKeyValue(state.uploads, 'id', image.id)

            if(theIndex !== 0 || theIndex === -1)
                theIndex = findIndexByKeyValue(state.uploads, 'name', image.name)

            if(theIndex >= 0 && theIndex !== -1){
                state.uploads[theIndex] = Object.assign( state.uploads[theIndex], image)
            }
        },
        UPDATE_CAPTION(state, image){
            // console.debug('Update caption', image, "new_caption" in image)
            let theIndex = findIndexByKeyValue(state.uploads, 'id', image.id)

            if("new_caption" in image){
                // console.debug('image has new caption', image)
                state.uploads[theIndex].caption = image.new_caption
                state.uploads[theIndex].title = image.new_caption
            }
            // console.debug("Caption updated", image.caption, image.new_caption);

        },
        DELETE_IMAGE(state, image){
            let theIndex = findIndexByKeyValue(state.uploads, 'id', image.id)

            if(theIndex >= 0 && theIndex !== -1){
                state.uploads = state.uploads.filter(img => img.id !== image.id)
            }
        },
        ADD_IMAGE_TO_QUEUE(state, image){
            state.queue = state.queue.concat([image])
        },
        ADD_UPLOADED_IMAGE(state, image){
            state.uploads = state.uploads.concat([image])
        },
        ADD_IMAGES(state, images){
            state.uploads = images
        },
        REMOVE_IMAGE_FROM_QUEUE(state, image){
            // console.debug("Remove image from queue", image.name, state.queue[0].name);
            state.queue = state.queue.filter(img => image.name !== img.name )
        },
        SET_FLASH(state, message){
            state.message = message
            setTimeout(function(){
                state.message = {}
            }, 5000)
        }
    },
    actions: {
        setFlash({commit}, message){
            commit('SET_FLASH', message)
        },
        getUploadedImages({commit}){
            commit('IS_LOADING', true)
            Vue.axios.get(krsp_file_upload.ajax_url, {
                    // method: 'GET',
                    params: {
                        'profile': krsp_file_upload.profile,
                        "action":'krsp_get_images',
                        'krspnc':krsp_file_upload.ajax_nonce
                    }
                })
            .then((response) => {
                commit('ADD_IMAGES', response.data.images)
                commit('IS_LOADING', false)
            })
            .catch((error) => {
                // console.error(error);
            });
        },
        deleteImage({commit}, image){
            commit('IS_DELETING', true)

            var formData = new FormData();
            formData.append('action', 'krsp_file_delete');
            formData.append('id', image.id);
            formData.append('member', krsp_file_upload.profile);
            formData.append('krspnc', krsp_file_upload.ajax_nonce);

            return Vue.axios.post(krsp_file_upload.ajax_url, formData)
            .then((response) => {
                if (response.data.success === true) {
                    commit('DELETE_IMAGE', image)
                    commit('IS_DELETING', false)
                    commit('SET_FLASH', {type: 1, text: 'Image deleted'})
                } else {
                    commit('SET_FLASH', {type: -1, text: 'There was an error deleting this item, please try again.'});
                }
            })
            .catch( (error) => {
                // console.debug(error);
                commit('SET_FLASH', {type: -1, text: error.message})
            });
        },
        addImageToQueue({commit}, image){
            commit('ADD_IMAGE_TO_QUEUE', image)
        },
        addUploadedImage({commit}, image){
            commit('ADD_UPLOADED_IMAGE', image)
            commit('REMOVE_IMAGE_FROM_QUEUE', image)
        },
        updateImage({commit}, image){
            commit('UPDATE_IMAGE', image)
            commit('UPDATE_CAPTION', image)
        },
        updateCaptions({commit, state}, uploads){
            uploads.forEach((image)=>{
                // commit('UPDATE_IMAGE', image)
                commit('UPDATE_CAPTION', image)
            })
            return new Promise(resolve =>resolve());
        },
        resetUploader({commit}){
            commit('RESET_UPLOADER');
        },
        saveImages({commit}, uploads){
            commit('IS_SAVING', true)

            const formData = new FormData();
            formData.append('action', 'krsp_files_save');
            formData.append('krspnc', krsp_file_upload.ajax_nonce);
            formData.append('uploads', JSON.stringify(uploads));
            formData.append('profile', krsp_file_upload.profile);
            return Vue.axios.post(krsp_file_upload.ajax_url, formData, {
                emulateJSON: true,
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then( (response) => {
                if (response.data.success === true) {
                    commit('IS_SAVING', false)
                    commit('SET_FLASH', { type: 1, text: 'Images saved succesfully'})
                    return response.data
                } else {
                    commit('SET_FLASH', {type: -1, text: 'There was an error saving the images, please try again.'});
                }
            })
            .catch( (error) => {
                // console.debug(error);
            });
        }
    },
    getters: {
        queue: state => state.queue,
        uploads: state => state.uploads,
        isLoading: state => state.isLoading,
        isSaving: state => state.isSaving,
        message: state => state.message,
        isUploading: state => state.queue.length > 0
    }
})


function findIndexByKeyValue(arraytosearch, key, valuetosearch) {
    for (var i = 0; i < arraytosearch.length; i++) {

        if (arraytosearch[i][key] == valuetosearch) {
            return i;
        }
    }
    return null;
}
