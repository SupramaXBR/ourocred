<?php

class Request
{
   private $url;
   private $curl;
   private $result; // Essa propriedade não é estritamente necessária se você retornar o resultado

   public function setUrl(string $url): void
   {
      $this->url = $url;
   }

   public function getUrl(): ?string
   {
      return $this->url;
   }

   /**
    * Faz uma requisição HTTP e retorna os dados decodificados de JSON.
    * Retorna um array em caso de sucesso ou null em caso de falha.
    */
   public function requestApi(): ?array
   {
      if (empty($this->url)) {
         error_log("Request::requestApi() - URL não definida.");
         return null;
      }

      $this->curl = curl_init($this->url);

      curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
      // curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false); // REMOVA OU CUIDADO!

      // **Recomendado: Ativar verificação SSL e configurar CA info**
      // Em ambiente de produção, é crucial verificar certificados SSL.
      // Se você tiver problemas com certificados, em vez de desativar,
      // tente apontar para um pacote de certificados CA confiável.
      // curl_setopt($this->curl, CURLOPT_CAINFO, '/path/to/cacert.pem');
      // Ou, se souber que o problema é auto-assinado e for aceitável APENAS para APIs CONFIÁVEIS:
      // curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false); // Apenas em casos MUITO específicos e conscientes

      // Definir um timeout para evitar que a requisição trave indefinidamente
      curl_setopt($this->curl, CURLOPT_TIMEOUT, 10); // 10 segundos de timeout

      $response = curl_exec($this->curl);
      $httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
      $curlError = curl_error($this->curl);
      $curlErrno = curl_errno($this->curl);

      curl_close($this->curl); // Sempre feche a sessão cURL

      // **Verifica se houve erro no cURL**
      if ($response === false) {
         error_log("Request::requestApi() - Erro cURL ($curlErrno): $curlError para URL: " . $this->url);
         return null;
      }

      // **Verifica o código de status HTTP**
      if ($httpCode >= 400) {
         error_log("Request::requestApi() - Erro HTTP $httpCode para URL: " . $this->url . ". Resposta: " . $response);
         return null;
      }

      // **Tenta decodificar o JSON**
      $data = json_decode($response, true);

      // **Verifica se a decodificação JSON foi bem-sucedida**
      if (json_last_error() !== JSON_ERROR_NONE) {
         error_log("Request::requestApi() - Erro ao decodificar JSON: " . json_last_error_msg() . " para URL: " . $this->url . ". Resposta: " . $response);
         return null;
      }

      return $data;
   }
}
