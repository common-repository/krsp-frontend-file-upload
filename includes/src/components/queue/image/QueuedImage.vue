<template>
  <li class="queued-image">
    <div class="image-container">
      <div class="image-src">
        <img v-bind:src="image.url" v-show="image.complete" />
        <i class="fa fa-picture-o" v-show="!image.complete"></i>
      </div>
      <div class="image-name">
        <p v-if="!image.complete && image.percent == 0">{{image.name}}</p>
        <p class="uploading" v-if="!image.complete && image.percent > 0 && image.percent <= 70">{{$t('queue.busy')}}</p>
        <p class="uploading" v-if="image.percent >= 70 && image.percent < 100">{{$t('queue.finishing')}}</p>
        <p class="uploading" v-if="image.percent == 100">{{$t('queue.saving')}}</p>
      </div>
    </div>
    <div class="image-progress">
      <div class="progress-bar" v-bind:style="{width: image.percent+'%', 'background-color': progressColor}"></div>
    </div>
  </li>
</template>

<script>
  import i18n from '../../../lang/lang'
  export default {
    props: ['image'],
    name: 'QueuedImage',
    data(){
      return {
        color: 'red'
      }
    },
    computed: {
      progressColor(){
        if(this.image.percent < 20 && this.image.percent > 0){
          return '#f35b04'
        }
        if(this.image.percent <= 50 && this.image.percent >= 20){
          return '#ffae03'
        }
        if(this.image.percent <= 80 && this.image.percent >= 50){
          return '#ffae03'
        }
        if(this.image.percent <= 90 && this.image.percent >= 80){
          return 'rgb(35, 188, 185)'
        }
        if(this.image.percent <= 100 && this.image.percent >= 90){
          return 'rgb(35, 188, 185)'
        }
      }
    }
  }
</script>

<style lang="scss" src="queued_image.scss"></style>