<template>
    <div class="container py-4">
      <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" @click="decreaseSize" :disabled="pdfWidth <= 500">Zoom -</button>
        <button class="btn btn-primary" @click="increaseSize" :disabled="pdfWidth >= 1100">Zoom +</button>
      </div>
      <div class="d-flex justify-content-center align-items-center">
        <vue-pdf-embed class="border" :source="pdfData" :width="pdfWidth"></vue-pdf-embed>
      </div>
    </div>
  </template>
  
  <script>
  import VuePdfEmbed from 'vue-pdf-embed';
  
  export default {
    components: {
      VuePdfEmbed
    },
    props: {
      file: Object
    },
    data() {
      return {
        pdfWidth: 1000
      };
    },
    computed: {
      pdfData() {
        return `data:application/pdf;base64,${this.file.contenu}`;
      }
    },
    methods: {
      increaseSize() {
        if (this.pdfWidth <= 1100) {
          this.pdfWidth += 100;
        }
      },
      decreaseSize() {
        if (this.pdfWidth >= 500) {
          this.pdfWidth -= 100;
        }
      }
    }
  }
  </script>
  