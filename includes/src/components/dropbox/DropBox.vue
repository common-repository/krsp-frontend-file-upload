<template>
  <div class="dropbox">
    <div class="dropbox-area bump-20 photo-placeholder square"
      id="dropbox"
      @dragover="dragOver"
      @dragleave="dragLeave"
      @drop="dragLeave"
      :class="{'drag-over' : dropStatus == 1}"
      >
      <div class="dropbox-content">
        <div class="indicator-inner">
          <i class="fas fa-cloud-upload-alt"></i>
          <h3 class="">{{$t('dropbox.text')}}</h3>
        </div>
      </div>
    </div>
    <div class="button-container">
			<button :disabled="hasQueue" id="file_uploader" class="button is-full btn btn-large">
				{{$t('dropbox.add_button')}}
			</button>
		</div>
  </div>
</template>

<script>
  import i18n from '../../lang/lang'
  export default {
    name: 'DropBox',
    data() {
      return {
        dropStatus: 0
      }
    },
    computed: {
      hasQueue() {
        return this.$store.getters.queue.length > 0;
      }
    },
    methods: {
      dragLeave() {
        this.dropStatus = 0;
      },
      dragOver() {
        this.dropStatus = 1
      },
    }
  }
</script>

<style lang="scss" src="drop_box.scss"></style>