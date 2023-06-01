<template>
	<div class="container py-4">
		<h1>choisir dans la liste</h1>
		
	</div>
</template>

<script>
import { mapState } from 'vuex';
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

	}
}
</script>
