import React, { Component } from 'react';
import axios from 'axios';

const LOGIN_URL = 'https://guideup.com.br';

class ForgotPassword extends Component {

  constructor(props) {
    super(props);

    this.state = {
      email: '',
      loading: false,
      error: '',
      success: ''
    }

    this.handleInputChanged = this.handleInputChanged.bind(this);
    this.handleSubmitClick = this.handleSubmitClick.bind(this);
  }

  handleInputChanged(e) {
    this.setState({ [e.target.name]: e.target.value });
  }

  handleSubmitClick(e) {
    if(this.state.email !== '') {
      if(!this.state.email.match(/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/)) {
        this.setState({ error: 'Email inválido', loading: false });
        return;
      }

      const request = axios.post(`${LOGIN_URL}/api/user/forgot`, {
        email: this.state.email
      },
    {      
      headers: {
        'Accept': 'application/json' 
      }
    });

    this.setState({ error: '', success: '', loading: true });

      request.then(response => {
        this.setState({ success: 'Foi enviado para o email informado um link para redefinir a senha!', error: '', loading: false });
      })
      request.catch(error => {
        if(error.response.data.ok == false) {
          this.setState({ error: error.response.data.message, success: '', loading: false });
        }
        else {
          this.setState({ error: 'Não foi possível enviar o link de redefinição de senha', success: '', loading: false });
        }
      })

      return;
    }
  }

  render() {
    return (
      <div className="app flex-row align-items-center">
        <div className="container">
          <div className="row justify-content-center">
            <div className="col-md-8 col-lg-5">
              <img className="col-md-12 text-center" src={'img/logo-horizontal.png'} />
              <div className="row">&nbsp;</div>
              <div className="card-group mb-0">
                <div className="card card-inverse card-primary">
                  <div className="card-block">
                    <h1>Recuperar senha</h1>
                    <p className="text-muted">Informe seu endereço de email</p>
                    <p className="text-danger">{this.state.error}</p>
                    <p className="text-info">{this.state.success}</p>
                    <div className="input-group mb-3">
                      <span className="input-group-addon"><i className="icon-user"></i></span>
                      <input type="text" name="email" className="form-control" placeholder="Email" value={this.state.email} onChange={this.handleInputChanged} disabled={this.state.loading}/>
                    </div>                    
                    {this.state.loading ?
                      <div className="row">
                        <div className="col-12"><i className="fa fa-refresh fa-spin"></i> Alterando senha ...</div>
                      </div>
                      : <div className="row">
                        <div className="col-6">
                        <button type="button" className="btn btn-default px-4" onClick={e => {this.props.history.goBack()}}>Voltar</button>
                        </div>
                        <div className="col-6">
                        <button type="button" className="btn btn-primary pull-right active px-4" onClick={this.handleSubmitClick}>Alterar senha</button>
                          </div>
                      </div>
                    }
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default ForgotPassword;
