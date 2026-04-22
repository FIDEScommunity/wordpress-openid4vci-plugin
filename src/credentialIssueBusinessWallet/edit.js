/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * Imports the InspectorControls component, which is used to wrap
 * the block's custom controls that will appear in in the Settings
 * Sidebar when the block is selected.
 *
 * Also imports the React hook that is used to mark the block wrapper
 * element. It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#inspectorcontrols
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, useBlockProps, InspectorAdvancedControls } from '@wordpress/block-editor';

/**
 * Imports the necessary components that will be used to create
 * the user interface for the block's settings.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/components/panel/#panelbody
 * @see https://developer.wordpress.org/block-editor/reference-guides/components/text-control/
 * @see https://developer.wordpress.org/block-editor/reference-guides/components/toggle-control/
 */
import { PanelBody, TextControl, TextareaControl } from '@wordpress/components';

/**
 * Imports the useEffect React Hook. This is used to set an attribute when the
 * block is loaded in the Editor.
 *
 * @see https://react.dev/reference/react/useEffect
 */
import { useEffect } from 'react';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @param {Object}   props               Properties passed to the function.
 * @param {Object}   props.attributes    Available block attributes.
 * @param {Function} props.setAttributes Function that updates individual attributes.
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { openidEndpoint, credentialIssueTemplateKey, authenticationHeaderName, authenticationToken, credentialData, formData, sessionData } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'openid4vc-issue' ) }>
					<TextControl
						label={ __(
							'Credential issue template key',
							'openid4vc-issue'
						) }
						value={ credentialIssueTemplateKey }
						onChange={ ( value ) =>
							setAttributes( { credentialIssueTemplateKey: value } )
						}
					/>
					<TextareaControl
						label={ __(
							'Credential data',
							'openid4vc-issue'
						) }
						value={ credentialData }
						onChange={ ( value ) =>
							setAttributes( { credentialData: value } )
						}
					/>
					<TextareaControl
						label={ __(
							'Form data',
							'openid4vc-issue'
						) }
						value={ formData }
						onChange={ ( value ) =>
							setAttributes( { formData: value } )
						}
					/>
					<TextareaControl
						label={ __(
							'Session data',
							'openid4vc-issue'
						) }
						value={ sessionData }
						onChange={ ( value ) =>
							setAttributes( { sessionData: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorAdvancedControls>
				<TextControl
					label={ __(
						'OpenID4VCI Endpoint',
						'openid4vc-issue'
					) }
					value={ openidEndpoint }
					onChange={ ( value ) =>
						setAttributes( { openidEndpoint: value } )
					}
				/>
				<TextControl
					label="Authentication header"
					value={authenticationHeaderName}
					onChange={( value ) =>
						setAttributes( { authenticationHeaderName: value } )
					}
				/>
				<TextControl
					label="Authentication token"
					value={authenticationToken}
					onChange={( value ) =>
						setAttributes( { authenticationToken: value } )
					}
				/>
			</InspectorAdvancedControls>

			<p {...useBlockProps()}><img decoding="async"
										 src="data:data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAPoAAAD6AQAAAACgl2eQAAAEL0lEQVR4Xu2YPa6rMBBGJ6KgCxuw5G24Y0thAwlsALbkzttAYgPQUVj4nSG3ILd4r7C7FytSfjgR9njmm89I+vvw8vuXX+MLvMcXeI8v8B7/FdBLlfY0hTS21bSbh6TBz3JeKgVMaRnbKK25hfjyC1dvfuEvBYFRls1bsMHLbZ8bvxyy9K4wMHhzl9j5ePN2rUVceWCVJYX5tksT0lrzKgywwL62h7M927TLayeqv0OdCZAPo1s+X78TJhNgbME8anN3aax/MvA9SgG9zCTbvTUPJ82+HHWU2nShJDCE+GjlIcu0S7fblXiSfkWBldDVBDN2qSIxJg/MxEoCk49NIKVNE6QLszi7BX4pCYxObsH2bdqSTb46ai1/crsgcLTm6ZYtzPfaDrsRZ7pEHZUE+rNMVs1kds28kjxd2nxJgDEkQ0pIa8eamcizTtd8KAHMDVmBlJHScu4Umn9RmHxgSHZkdVqeNumukXvyOv9ZDNhnKpF6WSU+3fwK86OtrpHMB3oxtzQ/hGBWU6h6mDR3HzqZCxBD3SNqM9i0s1La1nyNZD7Q6wKrLUWqcqJnOWI7dx/LzAUO1L6t2LLJzy/PJbmLKQsMeySTX8FuqUrJnvlsmksk8wHeOmxJoCpZLzofGxbblgQGz33t0WrhJL1EVi9HUYDVHaJ9imncUZtEYHFcJYFV+yBzQCSxizQUBE29SkFgU/ODEbV81xjW8e5ELmqfD3DTQd+xcKo2K+s9PXBB4NDaRB4pTLVAKSz04ukSyXxgFF5oS8IiTgkvZBr/ofb5wCFnJJ1RR+qZDFUj90sk8wE6iGqLzoG7G6lx1+naefMB/OHayt1RnvMdg63pwWRKAgcBDMyB2pRHO+Orux0LURLYtH1EET7AUEeoQbyfl0oBGEVCh78ShyM1bNPw2XkLADWtcBnFCAZ7x7eLKvMlkvnAoIYE2zA/KR90Hq/I70WBKdCkcCm2r9VRbwF5Wa5qnw+oC9Lmy+kj0rnovMzkoy3mA0Ge6n8iLoW7T+p+EZmSAKrSII962FRHPex22z8jmQ1wEicNqP1uFzkj2QSaS0ng0DSg284qXN72qvmfxZsPOHmoyHO0wZqiA9QO7rEkQCsZW3KsopVsiaygUdrpssx8YFRzgjMhE8hqyicimDe9UgzY0Pkax855jW+IgGaFpkQ5QB1pXa3OnjtVTfrMijwvCfROz02vpHM4aj1AKXyOYkBNJFH7+M5qdUFCnpcEkpYnrbZSI+HwD5Gr42WZ+QCdd/CoFovVGHahOtzSFwUmTvpJnRU5wBlEV6q9uCQwit5x4HTw40jtiCNtCwM036eWj3lRQXRefdpQGJh8fOjpgP5bIcJr/XGkzQf0Ea4eauYmVSTG5uOLD0UB8oGeS+i285lYCnJLn7WZDfxlfIH3+ALv8QXe4wu8x7+BP6qeIKKvI9tZAAAAAElFTkSuQmCC"/>
			</p>
		</>
	);
}
