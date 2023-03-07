/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;
const { dispatch } = wp.data;
const { PluginDocumentSettingPanel } = wp.editPost;
const { PostExcerptCheck } = wp.editor;
const { registerPlugin } = wp.plugins;

/**
 * Internal dependencies
 */
import PostExcerptForm from './panel';

// Remove core Post Excerpt panel.
( () => {
	dispatch( 'core/edit-post' ).removeEditorPanel( 'post-excerpt' );
} )();

// Add our own custom Post Excerpt panel.
const PostExcerpt = () => {
	return (
		<PostExcerptCheck>
			<PluginDocumentSettingPanel title={ __( 'Excerpt' ) }>
				<PostExcerptForm />
			</PluginDocumentSettingPanel>
		</PostExcerptCheck>
	);
};
registerPlugin( 'post-excerpt', { render: PostExcerpt } );
