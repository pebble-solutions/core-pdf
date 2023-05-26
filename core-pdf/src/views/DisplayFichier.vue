<template>
	<div class="container py-4">
		<div class="card">
			<Spinner v-if="!pending.fichier"></Spinner>
			<DisplayFile :file="fichier" v-else>

			</DisplayFile>
		</div>
	</div>
</template>

<script>
import { mapState } from 'vuex';
import axios from 'axios';
import DisplayFile from '../components/DisplayFile.vue'
import Spinner from '../components/pebble-ui/Spinner.vue';

export default {
	props: {
		cfg: Object,

	},

	components: {
		DisplayFile, Spinner
	},
	computed: {
		...mapState(['operations', 'openedElement']),
	},
	data() {
		return {
			pending: {
				fichier: false,
			},
			fichier: [],
		}
	},
	methods: {



		loadFichier(id) {
			this.pending.fichier = false
			axios.get('http://172.18.0.2/public/fichier/' + id).then(response => {
				this.$store.dispatch('refreshFichier', response.data);
				this.fichier = response.data;
			})
				.catch(error => {
					console.error(error);
				}).finally(this.pending.fichier = true);
		},


		deleteOperation(id) {
			axios.delete(`http://172.18.0.2/public/fichier/del/${id}`)
				.then(response => {
					// Gérer la réponse de suppression ici
					console.log(response);
					// Vous pouvez également déclencher un rechargement des opérations après suppression si nécessaire
				})
				.catch(error => {
					// Gérer les erreurs de suppression ici
					console.error(`Erreur lors de la suppression de l'opération avec ID ${id}:`, error);
				});
		}
	},
	beforeRouteUpdate(to) {
		if (to.params.id != this.fichier?.id) {
			console.log(to.params.id);
			this.loadFichier(to.params.id);
		}
	},
	beforeMount() {
		this.loadFichier(this.$route.params.id);
	}
}
</script>
