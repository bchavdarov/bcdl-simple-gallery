/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @param {Object} [props]           Properties passed from the editor.
 * @param {string} [props.className] Class name generated for the block.
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( props ) {
	const {
		className,
		attributes: { mediaIds },
		setAttributes,
	} = props;

	const onSelectImages = ( selection ) => {
		setAttributes( {
			mediaIds: selection.map(
					//( selectedImage ) => `${ selectedImage.id }`
					( selectedImage ) => `${ selectedImage.id }`
				),
		} );
	};

	return (
		<div className={ className }>
			<MediaUploadCheck>
				<MediaUpload
					onSelect={ onSelectImages }
					allowedTypes="image"
					value={ mediaIds }
					multiple="add"
					render={ ( { open } ) => (
						<Button onClick={ open }>
							{ __( 'Select from Media Library', 'bcdl-sg' ) }
						</Button>
					) }
				/>
			</MediaUploadCheck>
			[bcdlsimplegallery ids="{ mediaIds.toString() }"]
		</div>
	);
}
