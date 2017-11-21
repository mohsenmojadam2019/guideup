import React, { Component } from 'react';
import axios from 'axios';

const LOGIN_URL = 'https://guideup.com.br';

class ResetPassword extends Component {

  constructor(props) {
    super(props);

    
    const { token } = this.props.match.params;
    if(!token) {
        return;
    }

    this.state = {
      token: token,
      email: '',
      password: '',
      confirmPassword:'',
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
    if(this.state.email !== '' && this.state.password !== '' && this.state.confirmPassword !== '') {
      if(this.state.password.length < 8) {
        this.setState({ error: 'A senha deve ter no mínimo 8 caracteres', loading: false });
        return;
      }
      if(this.state.password !== this.state.confirmPassword) {
        this.setState({ error: 'As senhas não conferem', loading: false });
        return;
      }

      const request = axios.post(`${LOGIN_URL}/api/user/reset`, {
        token: this.state.token,
        email: this.state.email,
        password: this.state.password,
        password_confirmation: this.state.confirmPassword
      },
    {      
      headers: {
        'Accept': 'application/json' 
      }
    });

    this.setState({ error: '', success: '', loading: true });

      request.then(response => {
        this.setState({ success: 'Senha alterada com sucesso!', error: '', loading: false });
      })
      request.catch(error => {
        if(error.response.data.ok == false) {
          this.setState({ error: error.response.data.message, success: '', loading: false });
        }
        else {
          this.setState({ error: 'Não foi possível alterar a senha, verifique se o email informado está correto', success: '', loading: false });
        }
        console.log('====================================');
        console.log('ResetPassword error', error);
        console.log('====================================');
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
                    <h1>Redefinir senha</h1>
                    <p className="text-muted">Informe seu endereço de email e uma nova senha</p>
                    <p className="text-danger">{this.state.error}</p>
                    <p className="text-info">{this.state.success}</p>
                    <div className="input-group mb-3">
                      <span className="input-group-addon"><i className="icon-user"></i></span>
                      <input type="text" name="email" className="form-control" placeholder="Email" value={this.state.email} onChange={this.handleInputChanged} disabled={this.state.loading}/>
                    </div>
                    <div className="input-group mb-4">
                      <span className="input-group-addon"><i className="icon-lock"></i></span>
                      <input type="password" name="password" className="form-control" placeholder="Senha" value={this.state.password} onChange={this.handleInputChanged} disabled={this.state.loading}/>
                    </div>
                    <div className="input-group mb-4">
                      <span className="input-group-addon"><i className="icon-lock"></i></span>
                      <input type="password" name="confirmPassword" className="form-control" placeholder="Confirmar Senha" value={this.state.confirmPassword} onChange={this.handleInputChanged} disabled={this.state.loading}/>
                    </div>
                    <div className="row">
                      <div className="col-6">
                        {this.state.loading 
                          ? <div><i className="fa fa-refresh fa-spin"></i> Alterando senha ...</div>
                          : <button type="button" className="btn btn-primary active px-4" onClick={this.handleSubmitClick}>Alterar senha</button>
                        }
                        
                      </div>
                    </div>
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

export default ResetPassword;
