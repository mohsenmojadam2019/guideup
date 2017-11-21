import _ from 'lodash';
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import axios from 'axios';

import { token } from '../../index';

const GALLERY_URL = 'https://guideup.com.br/api/gallery';

const CANCEL_UPLOAD_MESSAGE = 'Image removed';

class ImageFile extends Component {

  constructor(props) {
    super(props);

    this.state = {
      imagePreviewUrls : this.configureImages(this.props)
    };
  }

  configureImages(props) {
    if (props.images && props.images.length > 0 && props.multiple !== false) {
			const teste = _.reduce(props.images, (obj, image, key) => {
        obj[key] = image;
        return obj;
      },{});
      return teste;
    }
    
		if (props.image && props.image.thumbnail_url && props.multiple === false) {
			return {0: props.image};
    }

    return null;
  }

  componentWillReceiveProps(nextProps) {
    if(this.props.image !== nextProps.image || this.props.images !== nextProps.images) {
      this.setState({
        imagePreviewUrls : this.configureImages(nextProps)
      });
    }
  }  

  maxImageKey() {
    return _.reduce(this.state.imagePreviewUrls, (lastIndex, index, key) => {
      const intKey = parseInt(key, 10);
      return intKey > lastIndex ? intKey : lastIndex;
    }, -1)
  }

  countImages() {
    return _.reduce(this.state.imagePreviewUrls, (sum, index) => {
      return sum + 1;
    }, 0)
  }

  uploadImage(file, key) {
    if(!this.props.uploadImage) {
      this.setState({
        imagePreviewUrls: { ...this.state.imagePreviewUrls, [key]: file }
      });
      if(this.props.onImageAdd) this.props.onImageAdd(file);

      return null;
    }
    
    const data = new FormData();
    data.append('file', file, file.name);
    data.append('description', file.name);
    data.append('position', '0');

    const source = axios.CancelToken.source();

    axios.post(`${GALLERY_URL}`, data, { 
      headers: {
        'Authorization': "Bearer " + token, 
        'Accept': 'application/json', 
        'content-type': 'multipart/form-data'
      },
      cancelToken: source.token 
    })
    .then(Response => {
      this.setState({
        imagePreviewUrls: { ...this.state.imagePreviewUrls, [key]: Response.data }
      });
      if(this.props.onImageAdd) this.props.onImageAdd(Response.data);
    })
    .catch(error => {
      console.log('erro ao upload', error);
      if(error.message && error.message !== CANCEL_UPLOAD_MESSAGE) {
        alert("Não foi possível fazer download da image " + file.name);
      }

      this.setState({
        imagePreviewUrls: _.omit(this.state.imagePreviewUrls, key)
      });
    });

    return source;
  }

  handleImageClick = e => {
    e.preventDefault();
		const filesList = e.target.files;
    if(filesList.length < 1) return;

    for (let i = 0; i < filesList.length; i++) {
      const file = filesList[i];
      
      if (!file.type.match('image.*')) {
        continue;
      }

      const reader = new FileReader();
      let key = 0;

      reader.onloadend = (upload) => {
        if (this.props.multiple === false) {
  						this.setState({
  							imagePreviewUrls: { [key]: { id: 0,  thumbnail_url: upload.target.result, cancelationToken: this.uploadImage(file, key) }}
  						});
  					} else {
              const previmagePreviewUrls = this.state.imagePreviewUrls;
              key = this.maxImageKey() + 1;
  						this.setState({
  							imagePreviewUrls: {...previmagePreviewUrls, [key]: { id: 0, thumbnail_url: upload.target.result, cancelationToken: this.uploadImage(file, key)}}
  						});
            }
      }
      reader.readAsDataURL(file)

    }
  }

  handleRemoveImageClick = (e, index) => {
    e.preventDefault();
    const image = this.state.imagePreviewUrls[index];
    //If image is uploading cancel the request
    if(image.id === 0 && image.cancelationToken) {
      image.cancelationToken.cancel(CANCEL_UPLOAD_MESSAGE);
    }
    else {
      if(this.props.onImageRemove) this.props.onImageRemove(image.id);
    }
    this.setState({
      imagePreviewUrls: _.omit(this.state.imagePreviewUrls, index)
    });
  }

  renderAddImageButton() {
    return (
      <div className="add-button" onClick={event => this.coverFileInput.click()}>
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style={{width: 35}} xmlnsXlink="http://www.w3.org/1999/xlink"
          x="0px"
          y="0px"
          viewBox="0 0 1000 1000"
          enableBackground="new 0 0 1000 1000"
          xmlSpace="preserve">
          <g>
            <path
              d="M500,10c13.5,0,25.1,4.8,34.7,14.4C544.2,33.9,549,45.5,549,59v392h392c13.5,0,25.1,4.8,34.7,14.4c9.6,9.6,14.4,21.1,14.4,34.7c0,13.5-4.8,25.1-14.4,34.6c-9.6,9.6-21.1,14.4-34.7,14.4H549v392c0,13.5-4.8,25.1-14.4,34.7c-9.6,9.6-21.1,14.4-34.7,14.4c-13.5,0-25.1-4.8-34.7-14.4c-9.6-9.6-14.4-21.1-14.4-34.7V549H59c-13.5,0-25.1-4.8-34.7-14.4C14.8,525.1,10,513.5,10,500c0-13.5,4.8-25.1,14.4-34.7C33.9,455.8,45.5,451,59,451h392V59c0-13.5,4.8-25.1,14.4-34.7C474.9,14.8,486.5,10,500,10L500,10z"
              />
          </g>
        </svg>
      </div>
    )
  }

  renderDeleteImageButton(index) {
    return (
      <div className="delete-button"  onClick={e => this.handleRemoveImageClick(e, index)}>
        <svg xmlns="http://www.w3.org/2000/svg" width="7.969" height="8" viewBox="0 0 7.969 8">
          <path id="X_Icon" data-name="X Icon"
          /* eslint-disable max-len */
          d="M562.036,606l2.849-2.863a0.247,0.247,0,0,0,0-.352l-0.7-.706a0.246,0.246,0,0,0-.352,0l-2.849,2.862-2.849-2.862a0.247,0.247,0,0,0-.352,0l-0.7.706a0.249,0.249,0,0,0,0,.352L559.927,606l-2.849,2.862a0.25,0.25,0,0,0,0,.353l0.7,0.706a0.249,0.249,0,0,0,.352,0l2.849-2.862,2.849,2.862a0.249,0.249,0,0,0,.352,0l0.7-.706a0.25,0.25,0,0,0,0-.353Z"
          /* eslint-enable max-len */
          transform="translate(-557 -602)"
          />
        </svg>
      </div>
    )
  }

  renderImagePreview(images) {
    const { multiple, width, height } = this.props;
    if(!images || images.length < 1) {
      return (
        <div className="image-upload" style={{...(width ? {width} : {}),...(height ? {height} : {})}}>
          { this.renderAddImageButton() }          
          {
            multiple === true 
            ? (<input type="file" style={{display:'none'}} ref={input => { this.coverFileInput = input; }} onChange={this.handleImageClick}/>) 
            : (<input type="file" style={{display:'none'}} ref={input => { this.coverFileInput = input; }} onChange={this.handleImageClick} multiple/>) 
          }
        </div>
      );
    }

    if(multiple === true) {
      return (<div>
        <div className="image-upload" style={{...(width ? {width} : {}),...(height ? {height} : {})}}>
          { this.renderAddImageButton() }
          <input type="file" style={{display:'none'}} ref={input => { this.coverFileInput = input; }} onChange={this.handleImageClick} multiple/>
        </div>
      { _.map(images, (image, key) => {
        return (
          <div key={key}
            className="image-upload" 
            style={{
              backgroundImage: `url(${image.thumbnail_url})`,
              ...(width ? {width} : {}),
              ...(height ? {height} : {})
              }}>
              {image.cancelationToken ? (<div className="loading-image"><i className="fa fa-spinner spin-animation"></i></div>) : null }
        { this.renderDeleteImageButton(key) }
      </div>
        );
      }) }
      </div>)
    }

    return (
      <div className="image-upload" 
        style={{
          backgroundImage: `url(${images[0].thumbnail_url})`,
          ...(width ? {width} : {}),
          ...(height ? {height} : {})
          }}>
          {images[0].cancelationToken ? (<div className="loading-image"><i className="fa fa-spinner spin-animation"></i></div>) : null }
        { this.renderDeleteImageButton(0) }
      </div>
    );
  }

  render() {
    const imagePreviewUrls = this.state.imagePreviewUrls;

    if(imagePreviewUrls && this.countImages() > 0) {
        return this.renderImagePreview(imagePreviewUrls);
    }
    //No image selected, show the add button
    return this.renderImagePreview()
  }
}

ImageFile.propTypes = {
  width: PropTypes.number,
  height: PropTypes.number,
  multiple: PropTypes.bool,
  images: PropTypes.array,
  onImageAdd: PropTypes.func,
  onImageRemove: PropTypes.func,
  uploadImage: PropTypes.bool
}
  
// Specifies the default values for props:
ImageFile.defaultProps = {
  multiple: false,
  images: [],
  width: 100,
  height: 100,
  uploadImage: true
};

export default ImageFile;
