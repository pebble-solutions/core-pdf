<template>
  <div class="container py-4">
    <div v-if="fileIsEmpty" class="text-center">Le fichier PDF est en cours de génération. Veuillez patienter.</div>
    <div v-else>
      <div class="d-flex justify-content-between mb-3">
        <button class="btn btn-danger" @click="deleteFile">Supprimer</button>
        <div>
          <button class="btn btn-primary" @click="decreaseSize" :disabled="pdfWidth <= 500">Zoom -</button>
          <button class="btn btn-primary" @click="increaseSize" :disabled="pdfWidth >= 1100">Zoom +</button>
        </div>
        <button class="btn btn-success" @click="downloadPDF">Télécharger</button>
      </div>
      <div class="d-flex justify-content-center align-items-center">
        <vue-pdf-embed class="border" :source="pdfData" :width="pdfWidth"></vue-pdf-embed>
      </div>
    </div>
  </div>
</template>

<script>
import VuePdfEmbed from 'vue-pdf-embed';
import axios from 'axios';

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
    },
    fileIsEmpty() {
      return !this.file.contenu;
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
    },
    deleteFile() {
      const apiUrl = `http://172.18.0.3/public/fichier/del/${this.file.id}`;

      axios
        .delete(apiUrl)
        .then(response => {
          // Suppression réussie
          this.$router.push('/operation');
          console.log(response);
        })
        .catch(error => {
          console.error("Une erreur s'est produite lors de la suppression du fichier.", error);
        });
    },
    downloadPDF() {
      const link = document.createElement('a');
      link.href = this.pdfData;
      link.download = this.file.fichier;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }
}
</script>
