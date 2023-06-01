<template>
	<div class="container py-4">
		choisir dans la liste
		
	</div>
</template>

<script>
import { mapState } from 'vuex';
import axios from 'axios';
export default {
	props: {
		cfg: Object,

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
			axios.get('http://172.18.0.3/public/fichier/' + id).then(response => {
				this.$store.dispatch('refreshFichier', response.data);
				this.fichier = response.data;
			})
				.catch(error => {
					console.error(error);
				}).finally(this.pending.fichier = true);
		},


		deleteOperation(id) {
			axios.delete(`http://172.18.0.3/public/fichier/del/${id}`)
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
	}
}
</script>
